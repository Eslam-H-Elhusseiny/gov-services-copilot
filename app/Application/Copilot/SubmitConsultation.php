<?php

namespace App\Application\Copilot;

use App\Application\Copilot\Contracts\CaseRepository;

final class SubmitConsultation
{
    public function __construct(private CaseRepository $cases) {}

    public function handle(int $userId, string $situation, string $model): int
    {
        $title = mb_substr($situation, 0, 45);

        $correlationId = 'GSC-CORR-'.strtoupper(bin2hex(random_bytes(5)));

        return $this->cases->createConsultation(
            $userId,
            $title,
            $situation,
            $model,
            $correlationId,
        );
    }
}
