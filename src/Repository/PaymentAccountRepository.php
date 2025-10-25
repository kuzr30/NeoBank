<?php

namespace App\Repository;

use App\Entity\PaymentAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentAccount>
 */
class PaymentAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentAccount::class);
    }

    /**
     * Récupère le compte de paiement unique
     * Retourne null si aucun compte n'existe
     */
    public function getPaymentAccount(): ?PaymentAccount
    {
        return $this->findOneBy([]);
    }

    /**
     * Vérifie si un compte de paiement existe déjà
     */
    public function hasPaymentAccount(): bool
    {
        return $this->count([]) > 0;
    }
}
