<?php

namespace App\Message;

class UserDataExportMessage
{
    public function __construct(
        private readonly string $csvFilePath,
        private readonly int $recordsCount,
        private readonly int $exportId
    ) {
    }

    public function getCsvFilePath(): string
    {
        return $this->csvFilePath;
    }

    public function getRecordsCount(): int
    {
        return $this->recordsCount;
    }

    public function getExportId(): int
    {
        return $this->exportId;
    }
}
