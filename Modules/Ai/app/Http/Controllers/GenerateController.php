<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Ai\Services\RunPodGenerateService;

class GenerateController extends Controller
{
    public function __construct(
        protected RunPodGenerateService $runPodGenerateService
    ) {}

    /**
     * POST api/v1/ai/generate
     *
     * Upstream is driven by env: `RUNPOD_BASE_URL`, `RUNPOD_CHAT_COMPLETIONS_PATH`,
     * `RUNPOD_ENDPOINT_STYLE` (auto|openai|invocations), `RUNPOD_INVOCATIONS_WRAPPER` (input|none).
     *
     * - Path `/v1/chat/completions` (or `endpoint_style=openai`): JSON body is OpenAI chat format.
     * - Path `/invocations` (or `endpoint_style=invocations`): same fields wrapped as
     *   `{ "input": { "model", "messages", ... } }` by default; set `RUNPOD_INVOCATIONS_WRAPPER=none`
     *   if your worker expects the OpenAI body at the root.
     *
     * Body:
     * - messages?: [{ "role": "user"|"assistant"|"system", "content": "..." }, ...]
     * - prompt?: string (mapped to a single user message if `messages` omitted)
     * - model?, temperature?, max_tokens?
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'prompt' => ['nullable', 'string', 'max:65535', 'required_without:messages'],
            'messages' => ['nullable', 'array', 'min:1', 'required_without:prompt'],
            'messages.*.role' => ['required_with:messages', 'string', Rule::in(['system', 'user', 'assistant'])],
            'messages.*.content' => ['required_with:messages', 'string', 'max:65535'],
            'model' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:131072'],
        ])->validate();

        try {
            if (! empty($validated['messages'])) {
                $result = $this->runPodGenerateService->chatCompletions([
                    'model' => $validated['model'] ?? null,
                    'messages' => $validated['messages'],
                    'temperature' => $validated['temperature'] ?? null,
                    'max_tokens' => $validated['max_tokens'] ?? null,
                ]);
            } else {
                $result = $this->runPodGenerateService->generate([
                    'prompt' => $validated['prompt'],
                    'model' => $validated['model'] ?? null,
                    'temperature' => $validated['temperature'] ?? null,
                    'max_tokens' => $validated['max_tokens'] ?? null,
                ]);
            }
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response?->status() ?? 502;
            $json = $e->response?->json();
            $upstream = is_array($json)
                ? ($json['error']['message'] ?? $json['error'] ?? $json['message'] ?? null)
                : null;
            if (is_array($upstream)) {
                $upstream = json_encode($upstream);
            }
            $upstream = $upstream !== null && $upstream !== '' ? (string) $upstream : null;
            $exceptionMessage = trim($e->getMessage());
            $parts = array_values(array_unique(array_filter(
                [$upstream, $exceptionMessage !== '' ? $exceptionMessage : null],
                fn ($v) => $v !== null && $v !== ''
            )));
            $message = $parts !== []
                ? implode(' | ', $parts)
                : __('ai::messages.runpod_request_failed');

            return $this->errorResponse(
                $message,
                $status >= 400 && $status < 600 ? $status : 502
            );
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->successResponse(
            __('ai::messages.generate_success'),
            $result
        );
    }
}
