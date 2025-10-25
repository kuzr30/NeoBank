<?php

namespace App\Repository;

use App\Entity\CompanySettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanySettings>
 */
class CompanySettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySettings::class);
    }

    /**
     * Récupère les paramètres de l'entreprise (il ne devrait y en avoir qu'un seul)
     */
    public function getCompanySettings(): ?CompanySettings
    {
        return $this->findOneBy([]) ?? $this->createDefaultSettings();
    }

    /**
     * Crée des paramètres par défaut si aucun n'existe
     */
    private function createDefaultSettings(): CompanySettings
    {
        $settings = new CompanySettings();
        $settings->setCompanyName('SEDEF BANK')
                ->setAddress('123 Avenue des Finances, 75001 Paris, France')
                ->setPhone('+33 1 23 45 67 89')
                ->setEmail('contact@bankit.fr')
                ->setWebsite('www.bankit.fr')
                ->setLegalMention('UN CRÉDIT VOUS ENGAGE ET DOIT ÊTRE REMBOURSÉ. VÉRIFIEZ VOS CAPACITÉS DE REMBOURSEMENT AVANT DE VOUS ENGAGER.');

        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();

        return $settings;
    }

    public function save(CompanySettings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CompanySettings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
