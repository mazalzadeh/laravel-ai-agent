<?php

namespace App\Services;

interface AIClientInterface
{
    // public function chat(string $message): array;
    /**
     * @param array $messages 
     * @param array $options
     */
    public function chat(array $messages, array $options = []): array;

    public function streamChat(array $messages, array $options = []): \Generator;

    public function embed(string $text): array;
}
