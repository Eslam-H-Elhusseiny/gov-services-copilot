<?php

namespace App\Application\Copilot\Contracts;

use App\Domain\Copilot\WorkflowState;

interface CaseRepository
{
    /**
     * Atomically create a consultation (case, officer message, workflow run in
     * WorkflowState::Received, and audit log) and return the new case id.
     */
    public function createConsultation(
        int $userId,
        string $title,
        string $situation,
        string $model,
        string $correlationId
    ): int;

    /**
     * Find a workflow run owned by the given user.
     *
     * Returns an array with:
     *   - id: int
     *   - case_id: int
     *   - state: WorkflowState
     *   - draft_response: ?string
     *
     * or null when the run does not exist or is not owned by the user.
     */
    public function findRunForUser(int $runId, int $userId): ?array;

    public function approveRun(
        int $runId,
        int $officerId,
        ?string $editedContent
    ): void;

    public function rejectRun(
        int $runId,
        int $officerId,
        string $reason
    ): void;

    public function deleteCaseForUser(int $caseId, int $userId): void;
}
