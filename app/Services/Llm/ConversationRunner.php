<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Log;

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


    /**
     * ConversationRunner constructor.
     *
     * @param LlmClient $client
     * @param array $availableTools List of executable tool objects.
     */
    public function __construct(
        protected LlmClient $client,
        protected array $availableTools = []
    ) {}


    /**
     * Runs the conversation loop until a final text response is received.
     *
     * @param string $prompt The user input.
     * @return string Final response from the assistant.
     */
    public function run(string $prompt): string
    {
        $this->messages[] = ['role' => 'user', 'content' => $prompt];

        while (true) {
            // 1. Get response from LLM (Fake or Real)
            $response = $this->client->chat($this->messages, $this->availableTools);
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
    protected function handleToolCalls(array $toolCalls): void
    {
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);


            // In MVP, we simulate the execution. In production,
            // you'd call $this->availableTools[$functionName]->execute($arguments).
            $result = $this->mockExecuteTool($functionName, $arguments);

            // Add tool result to messages with JSON_UNESCAPED_UNICODE for Persian support
            $this->messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'name' => $functionName,
                'content' => json_encode($result, JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    /**
     * Mocks the execution of a tool for local testing.
     *
     * @param string $name
     * @param array $args
     * @return array
     */
    protected function mockExecuteTool(string $name, array $args): array
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
