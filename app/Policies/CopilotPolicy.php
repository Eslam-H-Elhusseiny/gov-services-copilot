<?php

namespace App\Policies;

use App\Models\ServiceCase;
use App\Models\User;
use App\Models\WorkflowRun;

class CopilotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOfficer() || $user->isAdmin();
    }

    public function view(User $user, ServiceCase $case): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOfficer() && $case->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOfficer();
    }

    public function approve(User $user, WorkflowRun $run): bool
    {
        return $user->isOfficer() && $run->serviceCase?->user_id === $user->id;
    }

    public function reject(User $user, WorkflowRun $run): bool
    {
        return $user->isOfficer() && $run->serviceCase?->user_id === $user->id;
    }

    public function delete(User $user, ServiceCase $case): bool
    {
        return $user->isOfficer() && $case->user_id === $user->id;
    }
}
