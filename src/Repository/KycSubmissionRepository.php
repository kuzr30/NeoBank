<?php

namespace App\Repository;

use App\Entity\KycSubmission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KycSubmission>
 */
class KycSubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KycSubmission::class);
    }

    public function findByUser(User $user): ?KycSubmission
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.user = :user')
            ->andWhere('k.status != :replacedStatus')
            ->setParameter('user', $user)
            ->setParameter('replacedStatus', KycSubmission::STATUS_REPLACED)
            ->orderBy('k.submittedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingSubmissions(): array
    {
        return $this->createQueryBuilder('k')
            ->where('k.status = :status')
            ->setParameter('status', KycSubmission::STATUS_PENDING)
            ->orderBy('k.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSubmissionsForAdmin(): array
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.user', 'u')
            ->addSelect('u')
            ->orderBy('k.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getSubmissionStats(): array
    {
        $qb = $this->createQueryBuilder('k')
            ->select('k.status, COUNT(k.id) as count')
            ->where('k.status != :replacedStatus')
            ->setParameter('replacedStatus', KycSubmission::STATUS_REPLACED)
            ->groupBy('k.status');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            KycSubmission::STATUS_PENDING => 0,
            KycSubmission::STATUS_APPROVED => 0,
            KycSubmission::STATUS_REJECTED => 0,
            KycSubmission::STATUS_INCOMPLETE => 0,
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
        }

        return $stats;
    }

    public function findRecentSubmissions(int $limit = 10): array
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.user', 'u')
            ->addSelect('u')
            ->orderBy('k.submittedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
