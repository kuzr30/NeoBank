<?php

namespace App\Repository;

use App\Entity\TransferCode;
use App\Entity\Transfer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferCode>
 */
class TransferCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferCode::class);
    }

    /**
     * Trouve les codes expirés (6H après validation)
     */
    public function findExpiredCodes(): array
    {
        return $this->createQueryBuilder('tc')
            ->andWhere('tc.status = :status')
            ->andWhere('tc.expiresAt < :now')
            ->setParameter('status', 'validated')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le code actuel à valider pour un virement
     */
    public function findCurrentCodeForTransfer(Transfer $transfer): ?TransferCode
    {
        return $this->createQueryBuilder('tc')
            ->andWhere('tc.transfer = :transfer')
            ->andWhere('tc.codeOrder = :order')
            ->andWhere('tc.status = :status')
            ->setParameter('transfer', $transfer)
            ->setParameter('order', $transfer->getCurrentCodeIndex())
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le prochain ordre de code pour un virement
     */
    public function getNextCodeOrder(Transfer $transfer): int
    {
        $result = $this->createQueryBuilder('tc')
            ->select('MAX(tc.codeOrder)')
            ->andWhere('tc.transfer = :transfer')
            ->setParameter('transfer', $transfer)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }
}
