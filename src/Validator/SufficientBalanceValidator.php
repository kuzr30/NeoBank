<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SufficientBalanceValidator extends ConstraintValidator
{
    public function __construct(private Security $security)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SufficientBalance) {
            throw new UnexpectedTypeException($constraint, SufficientBalance::class);
        }

        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return; // Si pas d'utilisateur, on ne peut pas valider
        }

        // Récupérer le premier compte de l'utilisateur
        $userAccount = $user->getAccounts()->first();
        if (!$userAccount) {
            return; // Si pas de compte, on ne peut pas valider
        }

        // Récupérer le montant saisi dans le formulaire
        // $value contient toutes les données du formulaire
        $amount = null;
        if (is_array($value) && isset($value['amount'])) {
            $amount = $value['amount'];
        }
        
        if ($amount === null || $amount <= 0) {
            return; // Si pas de montant valide, on laisse les autres contraintes gérer
        }

        // Vérifier si le montant dépasse le solde disponible
        if (!$userAccount->canDebit((string)$amount)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ balance }}', number_format((float)$userAccount->getBalance(), 2, ',', ' '))
                ->atPath('amount')
                ->addViolation();
        }
    }
}
