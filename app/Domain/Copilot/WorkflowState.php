<?php

namespace App\Domain\Copilot;

enum WorkflowState: string
{
    case Received = 'RECEIVED';
    case EligibilityCheck = 'ELIGIBILITY_CHECK';
    case ProcedureResolution = 'PROCEDURE_RESOLUTION';
    case ResponseDrafting = 'RESPONSE_DRAFTING';
    case NeedsInformation = 'NEEDS_INFORMATION';
    case PendingApproval = 'PENDING_APPROVAL';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';
}
