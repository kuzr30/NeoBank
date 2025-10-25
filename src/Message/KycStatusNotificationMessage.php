<?php

namespace App\Message;

class KycStatusNotificationMessage
{
    public function __construct(
        private int $kycSubmissionId,
        private string $status
    ) {
    }

    public function getKycSubmissionId(): int
    {
        return $this->kycSubmissionId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
