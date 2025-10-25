<?php

namespace App\Controller\Banking\Trait;

use App\Entity\User;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\HttpFoundation\Response;

trait KycAccessTrait
{
    private KycService $kycService;
    private ProfessionalTranslationService $translationService;

    /**
     * Vérifie si l'utilisateur peut accéder aux services bancaires
     * et redirige vers KYC si nécessaire
     */
    private function checkKycAccess(): ?Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.kyc_verification_required', [], 'banking_common')
            );
            return $this->redirectToRoute('kyc_index');
        }
        
        return null;
    }
}
