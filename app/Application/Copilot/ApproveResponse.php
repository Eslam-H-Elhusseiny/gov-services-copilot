<?php

namespace App\Application\Copilot;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Domain\Copilot\WorkflowState;
use RuntimeException;

final class ApproveResponse
{
    public function __construct(private CaseRepository $cases) {}

    public function handle(int $runId, int $officerId, ?string $editedContent = null): void
    {
        $run = $this->cases->findRunForUser($runId, $officerId);

        if ($run === null) {
            throw new RuntimeException('Workflow run not found or not authorized for this officer.');
        }

        if ($run['state'] !== WorkflowState::PendingApproval) {
            throw new RuntimeException('Only workflow runs pending approval can be approved.');
        }

        $this->cases->approveRun($runId, $officerId, $editedContent);
    }
}
