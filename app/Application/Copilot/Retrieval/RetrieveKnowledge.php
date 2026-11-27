<?php

namespace App\Application\Copilot\Retrieval;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Retrieval\KeywordSearch;
use App\Domain\Copilot\Retrieval\RetrievalOutcome;
use App\Domain\Copilot\Retrieval\RetrievedChunk;
use App\Domain\Copilot\Retrieval\RrfFuser;
use App\Domain\Copilot\Vector\VectorIndex;

final class RetrieveKnowledge
{
    public function __construct(
        private EmbeddingProvider $embeddings,
        private VectorIndex $vectors,
        private KeywordSearch $keyword,
        private RrfFuser $fuser,
        private DocumentRepository $documents,
        private int $rrfK = 60,
    ) {}

    public function handle(int $userId, string $query, int $limit = 5): RetrievalOutcome
    {
        $vector = $this->embeddings->embed($query)->vector;

        $dense = [];
        $rank = 1;

        foreach ($this->vectors->search($vector, $limit) as $result) {
            $dense[] = ['id' => $result->id, 'score' => $result->score, 'rank' => $rank++];
        }

        $keywordResults = $this->keyword->search($query, $limit);

        $fused = $this->fuser->fuse($dense, $keywordResults, $this->rrfK);

        $rows = $this->documents->findChunksByIds(array_column($fused, 'id'));

        $byId = [];

        foreach ($rows as $row) {
            $byId[$row['id']] = $row;
        }

        $chunks = [];
        $versionsByDocument = [];

        foreach ($fused as $entry) {
            $row = $byId[$entry['id']] ?? null;

            if ($row === null) {
                continue;
            }

            $chunks[] = new RetrievedChunk(
                $row['id'],
                $row['content'],
                $row['document_id'],
                $row['document_version_id'],
                $row['filename'],
                $row['page_number'],
                $row['section'],
                $row['clause'],
                $entry['rrfScore'],
                $entry['rank'],
                $entry['sources'],
            );

            $versionsByDocument[$row['document_id']][$row['document_version_id']] = true;
        }

        $potentialConflicts = false;

        foreach ($versionsByDocument as $versions) {
            if (count($versions) > 1) {
                $potentialConflicts = true;

                break;
            }
        }

        return new RetrievalOutcome($chunks, count($chunks) > 0, $potentialConflicts);
    }
}
