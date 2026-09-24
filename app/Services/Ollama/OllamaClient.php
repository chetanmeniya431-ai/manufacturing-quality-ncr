<?php

namespace App\Services\Ollama;

use App\Exceptions\OllamaUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OllamaClient
{
    protected string $baseUrl;
    protected string $generationModel;
    protected string $embeddingModel;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai.ollama.base_url'), '/');
        $this->generationModel = config('ai.ollama.generation_model');
        $this->embeddingModel = config('ai.ollama.embedding_model');
        $this->timeout = config('ai.ollama.timeout');
    }

    /**
     * Embed a single string. Returns a flat array of floats.
     */
    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    /**
     * Embed multiple strings in one request. Returns an array of float arrays,
     * in the same order as the input.
     *
     * @param  string[]  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $response = $this->post('/api/embed', [
            'model' => $this->embeddingModel,
            'input' => array_values($texts),
        ]);

        $embeddings = $response['embeddings'] ?? null;

        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw new OllamaUnavailableException(
                "Ollama returned an unexpected embedding response for model \"{$this->embeddingModel}\"."
            );
        }

        return $embeddings;
    }

    /**
     * Send a chat completion request. $messages is an array of
     * ['role' => 'system'|'user'|'assistant', 'content' => string].
     */
    public function chat(array $messages): string
    {
        $response = $this->post('/api/chat', [
            'model' => $this->generationModel,
            'messages' => $messages,
            'stream' => false,
            // CPU inference time scales with output length — every token is a
            // full forward pass. Capping this keeps worst-case response time
            // bounded on modest hardware instead of letting the model ramble
            // for minutes on a verbose answer. ~400 tokens is generous for
            // "concise and practical" per the system prompt already in use.
            'options' => ['num_predict' => 400],
        ]);

        $content = $response['message']['content'] ?? null;

        if (! is_string($content) || $content === '') {
            throw new OllamaUnavailableException(
                "Ollama returned an empty response for model \"{$this->generationModel}\"."
            );
        }

        return trim($content);
    }

    /**
     * True if Ollama is reachable and both required models are pulled.
     *
     * @return array{ok: bool, message: ?string}
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => OllamaUnavailableException::unreachable($this->baseUrl, $e->getMessage())->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'message' => OllamaUnavailableException::unreachable($this->baseUrl, "HTTP {$response->status()}")->getMessage()];
        }

        $installed = collect($response->json('models', []))->pluck('name')->all();
        $required = [$this->generationModel, $this->embeddingModel];
        $missing = array_filter($required, fn ($model) => ! $this->modelInstalled($model, $installed));

        if (! empty($missing)) {
            return ['ok' => false, 'message' => OllamaUnavailableException::modelMissing(implode(', ', $missing))->getMessage()];
        }

        return ['ok' => true, 'message' => null];
    }

    protected function modelInstalled(string $wanted, array $installed): bool
    {
        foreach ($installed as $name) {
            if ($name === $wanted || str_starts_with($name, $wanted)) {
                return true;
            }
        }

        return false;
    }

    protected function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}{$path}", $payload);
        } catch (ConnectionException $e) {
            throw OllamaUnavailableException::unreachable($this->baseUrl, $e->getMessage());
        }

        if ($response->status() === 404) {
            $model = $payload['model'] ?? 'unknown';
            throw OllamaUnavailableException::modelMissing($model);
        }

        if (! $response->successful()) {
            $error = $response->json('error') ?? $response->body();

            if (is_string($error) && str_contains(strtolower($error), 'not found')) {
                throw OllamaUnavailableException::modelMissing($payload['model'] ?? 'unknown');
            }

            throw OllamaUnavailableException::unreachable($this->baseUrl, "HTTP {$response->status()}: {$error}");
        }

        return $response->json();
    }
}
