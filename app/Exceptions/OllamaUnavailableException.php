<?php

namespace App\Exceptions;

use RuntimeException;

class OllamaUnavailableException extends RuntimeException
{
    public static function unreachable(string $baseUrl, string $detail): self
    {
        return new self("Could not reach Ollama at {$baseUrl}: {$detail}");
    }

    public static function modelMissing(string $model): self
    {
        return new self("Ollama model \"{$model}\" is not available. Pull it with: ollama pull {$model}");
    }
}
