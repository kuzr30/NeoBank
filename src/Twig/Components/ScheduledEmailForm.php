<?php

namespace App\Twig\Components;

use App\Entity\ScheduledEmail;
use App\Entity\User;
use App\Enum\AccountIncompleteReason;
use App\Enum\CreditApplicationIncompleteReason;
use App\Enum\EmailStatus;
use App\Enum\EmailTemplateType;
use App\Enum\KycRejectionReason;
use App\Form\ScheduledEmailType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ScheduledEmailForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    public ?EmailTemplateType $templateType = null;

    #[LiveProp]
    public ?ScheduledEmail $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $scheduledEmail = $this->initialFormData ?? new ScheduledEmail();
        $scheduledEmail->setLocale('fr');
        $scheduledEmail->setStatus(EmailStatus::PENDING);
        
        if ($this->getUser()) {
            $scheduledEmail->setCreatedBy($this->getUser());
        }

        return $this->createForm(ScheduledEmailType::class, $scheduledEmail);
    }

    public function getAvailableReasons(): array
    {
        if (!$this->templateType) {
            return [];
        }

        return match($this->templateType) {
            EmailTemplateType::KYC_REJECTED => $this->getKycReasons(),
            EmailTemplateType::INCOMPLETE_ACCOUNT => $this->getAccountIncompleteReasons(),
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => $this->getCreditReasons(),
            default => [],
        };
    }

    private function getKycReasons(): array
    {
        $reasons = [];
        foreach (KycRejectionReason::cases() as $reason) {
            $reasons[] = [
                'value' => $reason->value,
                'label' => $reason->getLabel(),
                'description' => $reason->getDescription(),
            ];
        }
        return $reasons;
    }

    private function getAccountIncompleteReasons(): array
    {
        $reasons = [];
        foreach (AccountIncompleteReason::cases() as $reason) {
            $reasons[] = [
                'value' => $reason->value,
                'label' => $reason->getLabel(),
                'description' => $reason->getDescription(),
            ];
        }
        return $reasons;
    }

    private function getCreditReasons(): array
    {
        $reasons = [];
        foreach (CreditApplicationIncompleteReason::cases() as $reason) {
            $reasons[] = [
                'value' => $reason->value,
                'label' => $reason->getLabel(),
                'description' => $reason->getDescription(),
            ];
        }
        return $reasons;
    }

    public function requiresReasons(): bool
    {
        if (!$this->templateType) {
            return false;
        }

        return $this->templateType->requiresReasons();
    }
}
