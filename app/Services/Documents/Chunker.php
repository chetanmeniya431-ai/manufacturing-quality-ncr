<?php

namespace App\Services\Documents;

class Chunker
{
    /**
     * Split page texts into overlapping chunks, tracking an estimated source
     * page per chunk. Chunk size / overlap are expressed in "tokens",
     * approximated here as whitespace-separated words.
     *
     * @param  string[]  $pages  0-indexed page texts (page N is index N-1)
     * @return array<int, array{text: string, page: int}>
     */
    public function chunkPages(array $pages, ?int $chunkSize = null, ?int $overlap = null): array
    {
        $chunkSize = $chunkSize ?? config('ai.chunk_size');
        $overlap = $overlap ?? config('ai.chunk_overlap');

        $words = [];
        $wordPages = [];

        foreach ($pages as $pageIndex => $pageText) {
            $pageNumber = $pageIndex + 1;
            $paragraphs = preg_split('/\n{2,}/', trim((string) $pageText)) ?: [];

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph));
                if ($paragraph === '') {
                    continue;
                }
                foreach (explode(' ', $paragraph) as $word) {
                    $words[] = $word;
                    $wordPages[] = $pageNumber;
                }
                $words[] = "\n\n";
                $wordPages[] = $pageNumber;
            }
        }

        if (empty($words)) {
            return [];
        }

        $chunks = [];
        $step = max(1, $chunkSize - $overlap);
        $total = count($words);

        for ($start = 0; $start < $total; $start += $step) {
            $slice = array_slice($words, $start, $chunkSize);
            $chunkText = trim(str_replace(" \n\n ", "\n\n", implode(' ', $slice)));

            if ($chunkText !== '') {
                $chunks[] = [
                    'text' => $chunkText,
                    'page' => $wordPages[$start] ?? 1,
                ];
            }

            if ($start + $chunkSize >= $total) {
                break;
            }
        }

        return $chunks;
    }
}
