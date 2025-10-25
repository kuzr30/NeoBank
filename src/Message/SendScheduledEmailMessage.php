<?php

namespace App\Message;

final readonly class SendScheduledEmailMessage
{
    public function __construct(
        private int $scheduledEmailId
    ) {
    }

    public function getScheduledEmailId(): int
    {
        return $this->scheduledEmailId;
    }
}
