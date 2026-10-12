<?php

namespace App\Agent\Services;

use App\Agent\Services\ToolRegistry;
use App\DTO\ChatResponseDTO;
use App\Services\AIClientInterface;
use RuntimeException;

class ToolExecutionHandler
{
    public function __construct(
        protected AIClientInterface $aiclient,
        protected ToolRegistry $toolRegistry
    ){}


    public function handle(
        string $userInput,
        ?string $context = null,
        int $maxIterations = 5
    ):string
    {
        $messages=[];

        if($context){
            $messages[]=['role'=>'system','content'=>$context];
        }

        $messages[]=['role'=>'user','content'=>$userInput];

        $iterations=0;

        while($iterations<$maxIterations){
            $iterations++;

            $response=$this->aiclient->chat($messages,[
                'tools'=>$this->toolRegistry->toOpenAiTools(),
            ]);

            if(!($response['success']??false)){
                $errorMessage=$response['error']->message ??'Unknown AI error occurred.';
                throw new RuntimeException("AI provider request failed:{$errorMessage}");
            }

            $dto=$response['data'];

            // If the tool model is not called, the final response is ready.
            if(!$dto->hasToolCalls()){
                return $dto->content ?? '';
            }

            // We save the assistant's own message (the assistant that initiated the Truecaller request)
            $messages[]=[
                'role'=>'assistant',
                'content'=>$dto->content,
                'tool_calls'=>array_map(function($toolCall){
                    return[
                        'id'=>$toolCall->id,
                        'type'=>'function',
                        'function'=>[
                            'name'=>$toolCall->name,
                            'arguments'=>json_encode($toolCall->arguments),
                        ]
                    ];
                },$dto->getToolCalls()),
            ];

            foreach($dto->getToolCalls() as $toolCall){
                $tool=$this->toolRegistry->get($toolCall->name);
                $result=$tool->execute($toolCall->arguments);

                $messages[]=[
                    'role'=>'tool',
                    'tool_call_id'=>$toolCall->id,
                    'content'=>(string)$result,
                ];
            }
        }
        throw new RuntimeException("Maximum tool execution iterations ({$maxIterations}) exceeded.");
    }
}
