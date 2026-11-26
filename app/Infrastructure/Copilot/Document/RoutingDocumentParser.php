<?php

namespace App\Infrastructure\Copilot\Document;

use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\ParsedDocument;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class RoutingDocumentParser implements DocumentParser
{
    public function __construct(
        private DocumentParser $pdf,
        private DocumentParser $docx,
    ) {}

    public function parse(string $relativePath): ParsedDocument
    {
        $disk = Storage::disk(config('documents.disk', 'local'));

        if (! $disk->exists($relativePath)) {
            throw new InvalidArgumentException('Document file not found.');
        }

        $absolutePath = $disk->path($relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->pdf->parse($absolutePath),
            'docx' => $this->docx->parse($absolutePath),
            default => throw new InvalidArgumentException(sprintf('Unsupported document type [%s].', $extension)),
        };
    }
}
