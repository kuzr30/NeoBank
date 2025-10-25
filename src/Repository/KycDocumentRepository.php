<?php

namespace App\Repository;

use App\Entity\KycDocument;
use App\Entity\KycSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KycDocument>
 */
class KycDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KycDocument::class);
    }

    public function findBySubmissionAndType(KycSubmission $submission, string $type): ?KycDocument
    {
        return $this->findOneBy([
            'kycSubmission' => $submission,
            'type' => $type
        ]);
    }

    public function findBySubmission(KycSubmission $submission): array
    {
        return $this->findBy(
            ['kycSubmission' => $submission],
            ['type' => 'ASC']
        );
    }

    public function getDocumentCountByType(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.type, COUNT(d.id) as count')
            ->groupBy('d.type');

        $results = $qb->getQuery()->getResult();
        
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['type']] = (int) $result['count'];
        }

        return $stats;
    }
}
