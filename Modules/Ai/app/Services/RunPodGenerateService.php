<?php

namespace Modules\Ai\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunPodGenerateService
{
    /**
     * OpenAI-compatible chat, or RunPod /invocations with optional { "input": ... } wrapper.
     *
     * @param  array{
     *     model?: string|null,
     *     messages: array<int, array{role: string, content: string}>,
     *     temperature?: float|null,
     *     max_tokens?: int|null,
     * }  $payload
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function chatCompletions(array $payload): array
    {
        $baseUrl = config('ai.runpod.base_url', '');
        if ($baseUrl === '') {
            throw new \InvalidArgumentException(__('ai::messages.runpod_base_url_required'));
        }

        $apiKey = (string) config('ai.runpod.api_key', '');
        if ($apiKey === '') {
            throw new \InvalidArgumentException(__('ai::messages.runpod_api_key_required'));
        }

        $path = config('ai.runpod.chat_completions_path', '/v1/chat/completions');
        $url = $baseUrl.'/'.ltrim($path, '/');

        $messages = $payload['messages'] ?? [];
        if (! is_array($messages) || $messages === []) {
            throw new \InvalidArgumentException(__('ai::messages.messages_required'));
        }

        $openAiBody = [
            'model' => $payload['model'] ?? config('ai.runpod.default_model'),
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? config('ai.runpod.default_temperature', 0.7),
        ];

        if (array_key_exists('max_tokens', $payload) && $payload['max_tokens'] !== null) {
            $openAiBody['max_tokens'] = (int) $payload['max_tokens'];
        }

        $style = $this->resolveEndpointStyle();
        $body = $this->buildHttpBody($openAiBody, $style);

        $timeout = (int) config('ai.runpod.timeout', 120);

        Log::debug('RunPod AI request', [
            'url' => $url,
            'endpoint_style' => $style,
            'model' => $openAiBody['model'],
            'messages_count' => count($openAiBody['messages']),
        ]);

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        if ($response->failed()) {
            Log::warning('RunPod AI request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $response->throw();
        }

        $json = $response->json() ?? [];

        return $style === 'invocations'
            ? $this->normalizeInvocationsResponse($json)
            : $json;
    }

    /**
     * @param  array{model?: string|null, prompt: string, temperature?: float|null, max_tokens?: int|null}  $payload
     * @return array<string, mixed>
     */
    public function generate(array $payload): array
    {
        $prompt = $payload['prompt'] ?? '';
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->chatCompletions([
            'model' => $payload['model'] ?? null,
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? null,
            'max_tokens' => $payload['max_tokens'] ?? null,
        ]);
    }

    private function resolveEndpointStyle(): string
    {
        $explicit = strtolower((string) config('ai.runpod.endpoint_style', 'auto'));
        if ($explicit === 'openai') {
            return 'openai';
        }
        if ($explicit === 'invocations') {
            return 'invocations';
        }

        $path = strtolower((string) config('ai.runpod.chat_completions_path', ''));

        return str_contains($path, 'invocations') ? 'invocations' : 'openai';
    }

    /**
     * @param  array<string, mixed>  $openAiBody
     * @return array<string, mixed>
     */
    private function buildHttpBody(array $openAiBody, string $style): array
    {
        if ($style !== 'invocations') {
            return $openAiBody;
        }

        $wrapper = strtolower((string) config('ai.runpod.invocations_wrapper', 'input'));
        if (! in_array($wrapper, ['input', 'none'], true)) {
            $wrapper = 'input';
        }

        return $wrapper === 'none'
            ? $openAiBody
            : ['input' => $openAiBody];
    }

    /**
     * Flatten common RunPod serverless shapes so clients still get usable JSON.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function normalizeInvocationsResponse(array $json): array
    {
        if (! array_key_exists('output', $json)) {
            return $json;
        }

        $output = $json['output'];

        if (is_array($output) && isset($output['choices'])) {
            return $output;
        }

        if (is_string($output)) {
            $decoded = json_decode($output, true);
            if (is_array($decoded) && isset($decoded['choices'])) {
                return $decoded;
            }

            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => $output,
                        ],
                    ],
                ],
                'runpod' => ['wrapped_output' => true],
            ];
        }

        if (is_array($output)) {
            return $json;
        }

        return $json;
    }
}
