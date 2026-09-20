<?php

namespace App\Services\Documents;

use Smalot\PdfParser\Parser as PdfParser;

class TextExtractor
{
    /**
     * Extract text per page. Returns an array of page texts (1-indexed pages
     * become array positions 0..n-1) so callers can estimate which page a
     * chunk came from.
     *
     * @return string[]
     */
    public function extractPages(string $absolutePath): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($absolutePath);

        $pages = [];
        foreach ($pdf->getPages() as $page) {
            $pages[] = $page->getText();
        }

        if (empty($pages)) {
            $pages[] = $pdf->getText();
        }

        return $pages;
    }

    public function extract(string $absolutePath): string
    {
        return implode("\n\n", $this->extractPages($absolutePath));
    }
}
