<?php

namespace App\Services\Llm;

use App\AI\ToolExecutor;
use App\AI\ToolRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Class ConversationRunner
 *
 * Orchestrates the flow between the LLM client, tool execution,
 * and message history management.
 */
class ConversationRunner
{
    /** @var array The ongoing message history. */
    protected array $messages = [];
    private int $maxIterations;


    /**
     * ConversationRunner constructor.
     *
     * @param LlmClient $client
     * @param array $availableTools List of executable tool objects.
     */
    public function __construct(
        protected LlmClient $client,
        protected ToolRegistry $registry,
        protected ToolExecutor $executor,
        int $maxIterations = 5,
        /*protected array $availableTools = []*/
    ) {
        if($maxIterations<1){
            throw new \InvalidArgumentException('Maximum conversation iterations must be at least 1.');
        }

        $this->maxIterations=$maxIterations;
    }


    /**
     * Runs the conversation loop until a final text response is received.
     *
     * @param string $prompt The user input.
     * @return string Final response from the assistant.
     */
    public function run(string $prompt): string
    {
        $this->messages[] = ['role' => 'user', 'content' => $prompt];

        $iteration = 0;

        while (true) {

            if($iteration>= $this->maxIterations){
                throw new \RuntimeException(
                    "Conversation loop exceeded maximum allowed iterations ({$this->maxIterations})."
                );
            }

            $iteration++;

            // Get OpenAI format payload of all registered tools
            $availableTools = $this->registry->toApiFormat();

            // 1. Get response from LLM (Fake or Real)
            $response = $this->client->chat($this->messages, $availableTools);
            $message = $response['choices'][0]['message'];

            // 2. Add assistant's message (even if it's a tool call) to history
            $this->messages[] = $message;

            // 3. Check if LLM wants to call a tool
            if (!empty($message['tool_calls'])) {
                $this->handleToolCalls($message['tool_calls']);
                continue; // Re-run the loop with tool results added to history
            }

            // 4. If no tool_calls, it's the final response
            return $message['content'];
        }
    }

    /**
     * Processes tool calls by matching them with local tool instances.
     *
     * @param array $toolCalls
     * @return void
     */
    // protected function handleToolCalls(array $toolCalls): void
    // {
    //     foreach ($toolCalls as $toolCall) {
    //         $functionName = $toolCall['function']['name'];
    //         $arguments = json_decode($toolCall['function']['arguments'], true);


    //         // In MVP, we simulate the execution. In production,
    //         // you'd call $this->availableTools[$functionName]->execute($arguments).
    //         /*$result = $this->mockExecuteTool($functionName, $arguments);*/

    //         // Execute the tool using the real injected ToolExecutor
    //         $result = $this->executor->execute($functionName, $arguments);

    //         // Add tool result to messages with JSON_UNESCAPED_UNICODE for Persian support
    //         $this->messages[] = [
    //             'role' => 'tool',
    //             'tool_call_id' => $toolCall['id'],
    //             'name' => $functionName,
    //             'content' => json_encode($result, JSON_UNESCAPED_UNICODE)
    //         ];
    //     }
    // }

    /**
     * Execute tool calls and append their results to the conversation history.
     *
     * Invalid arguments and execution failures are returned to the LLM as
     * structured tool messages so that it can correct or explain the failure.
     *
     * @param array<int, array<string, mixed>> $toolCalls
     */
    private function handleToolCalls(array $toolCalls): void
    {
        foreach ($toolCalls as $toolCall) {
            $toolCallId = $toolCall['id'] ?? null;
            $function = $toolCall['function'] ?? null;


            /*
            * Without a valid tool-call ID, no protocol-compliant tool response
            * can be appended to the conversation.
            */
            if (!is_string($toolCallId) || $toolCallId === '') {
                report(new RuntimeException(
                    'The LLM returned a tool call without a valid ID.'
                ));

                continue;
            }

            if (!is_array($function)) {
                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: 'unknown',
                    type: 'invalid_tool_call',
                    message: 'The tool call does not contain a valid function object.',
                );

                continue;
            }

            $functionName = $function['name'] ?? null;

            if (!is_string($functionName) || $functionName === '') {
                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: 'unknown',
                    type: 'invalid_tool_call',
                    message: 'The tool call does not contain a valid function name.',
                );

                continue;
            }


            try {
                $arguments = $this->decodeToolArguments($function['arguments'] ?? null);

                $result = $this->executor->execute($functionName, $arguments);

                $this->appendToolMessage(
                    toolCallId: $toolCallId,
                    functionName: $functionName,
                    content: $result,
                );
            } catch (JsonException $exception) {
                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: $functionName,
                    type: 'invalid_json',
                    message: 'The tool arguments contain invalid JSON.',
                    details: ['reason' => $exception->getMessage()]
                );
            } catch (ValidationException $exception) {
                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: $functionName,
                    type: 'validation_error',
                    message: 'The tool arguments failed validation.',
                    details: ['errors' => $exception->errors()]
                );
            } catch (RuntimeException $exception) {
                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: $functionName,
                    type: 'tool_execution_error',
                    message: $exception->getMessage(),
                );
            } catch (Throwable $exception) {
                report($exception);

                $this->appendToolError(
                    toolCallId: $toolCallId,
                    functionName: $functionName,
                    type: 'internal_error',
                    message: 'An unexpected error occurred while executing the tool.',
                );
            }
        }
    }

    /**
     * Mocks the execution of a tool for local testing.
     *
     * @param string $name
     * @param array $args
     * @return array
     */
    /*protected function mockExecuteTool(string $name, array $args): array
    {
        if ($name === 'get_order_status') {
            return [
                'order_id' => $args['order_id'],
                'status' => 'در حال ارسال',
                'payment' => 'پرداخت شده',
                'courier' => 'تیپاکس',
                'delivery_date' => '۱۴۰۳/۰۵/۱۰'
            ];
        }

        return ['error' => 'Tool not found'];
    }*/


    /**
     * Decode and normalize JSON arguments returned by the LLM.
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeToolArguments(mixed $rawArguments): array
    {
        if (!is_string($rawArguments)) {
            throw new JsonException(
                'Tool arguments must be provided as a JSON string.'
            );
        }

        $arguments = json_decode(
            json: $rawArguments,
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );

        if (!str_starts_with(ltrim($rawArguments), '{')) {
            throw new JsonException('Tool arguments must be encoded as a JSON object.');
        }

        if (!is_array($arguments)) {
            throw new JsonException('Tool arguments must decode to an object.');
        }

        return $arguments;
    }

    /**
     * Append a tool result to the conversation history.
     *
     * @param array<string, mixed> $content
     *
     * @throws JsonException
     */
    private function appendToolMessage(
        string $toolCallId,
        string $functionName,
        array $content,
    ): void {
        $this->messages[] = [
            'role' => 'tool',
            'tool_call_id' => $toolCallId,
            'name' => $functionName,
            'content' => json_encode(
                value: $content,
                flags: JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR,
            ),
        ];
    }

    /**
     * Append a structured tool error to the conversation history.
     *
     * @param array<string, mixed> $details
     *
     * @throws JsonException
     */
    private function appendToolError(
        string $toolCallId,
        string $functionName,
        string $type,
        string $message,
        array $details = [],
    ): void {
        $error = [
            'success' => false,
            'error' => [
                'type' => $type,
                'message' => $message,
            ],
        ];

        if ($details !== []) {
            $error['error']['details'] = $details;
        }

        $this->appendToolMessage(
            toolCallId: $toolCallId,
            functionName: $functionName,
            content: $error,
        );
    }

    /**
     * Returns the full conversation history.
     *
     * @return array
     */
    public function getHistory(): array
    {
        return $this->messages;
    }
}
