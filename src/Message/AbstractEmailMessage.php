<?php

namespace App\Message;

/**
 * Message de base pour tous les emails
 */
abstract class AbstractEmailMessage
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $template,
        public readonly array $context = [],
        public readonly ?string $toName = null,
        public readonly ?string $fromEmail = null,
        public readonly ?string $fromName = null,
        public readonly ?string $translationDomain = null
    ) {
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getToName(): ?string
    {
        return $this->toName;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }
}
