<?php

namespace App\Domain\Copilot\Document;

interface DocumentParser
{
    public function parse(string $relativePath): ParsedDocument;
}
