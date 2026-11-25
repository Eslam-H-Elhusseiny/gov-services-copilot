<?php

namespace App\Application\Copilot;

use App\Application\Copilot\Contracts\CaseRepository;

final class DeleteCase
{
    public function __construct(private CaseRepository $cases) {}

    public function handle(int $caseId, int $userId): void
    {
        $this->cases->deleteCaseForUser($caseId, $userId);
    }
}
