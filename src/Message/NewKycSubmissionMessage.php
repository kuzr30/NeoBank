<?php

namespace App\Message;

class NewKycSubmissionMessage
{
    public function __construct(
        private int $submissionId
    ) {
    }

    public function getSubmissionId(): int
    {
        return $this->submissionId;
    }
}
