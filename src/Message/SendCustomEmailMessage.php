<?php

namespace App\Message;

final readonly class SendCustomEmailMessage
{
    public function __construct(
        private int $customEmailId
    ) {
    }

    public function getCustomEmailId(): int
    {
        return $this->customEmailId;
    }
}
