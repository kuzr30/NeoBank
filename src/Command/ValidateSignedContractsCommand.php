<?php

namespace App\Command;

use App\Entity\CardSubscription;
use App\Entity\ContractSubscription;
use App\Service\CardSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:validate-signed-contracts',
    description: 'Validate signed contracts and create cards',
)]
class ValidateSignedContractsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CardSubscriptionService $cardSubscriptionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🏦 Validation des Contrats Signés et Création des Cartes');

        // Trouver les contrats signés avec souscriptions approuvées
        $signedContracts = $this->entityManager->getRepository(ContractSubscription::class)
            ->createQueryBuilder('c')
            ->join('c.cardSubscription', 's')
            ->where('c.status = :signed')
            ->andWhere('s.status = :approved')
            ->setParameter('signed', 'signed')
            ->setParameter('approved', 'approved')
            ->getQuery()
            ->getResult();

        if (empty($signedContracts)) {
            $io->warning('Aucun contrat signé avec souscription approuvée trouvé');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Trouvé %d contrat(s) signé(s) à traiter', count($signedContracts)));

        foreach ($signedContracts as $contract) {
            $subscription = $contract->getCardSubscription();
            
            $io->section("Traitement du contrat {$contract->getReference()}");
            $io->info("Client : {$subscription->getUser()->getFirstName()} {$subscription->getUser()->getLastName()}");
            $io->info("Carte : {$subscription->getCardBrand()} {$subscription->getCardType()}");
            
            try {
                // Créer la carte
                $card = $this->cardSubscriptionService->createCard($subscription);
                
                $io->success("✅ Carte créée avec succès !");
                $io->info("Numéro : {$card->getCardNumber()}");
                $io->info("Code PIN : {$card->getPinCode()}");
                $io->info("Expiration : {$card->getExpiryDate()->format('m/Y')}");
                
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la création de la carte : {$e->getMessage()}");
            }
        }

        $io->success('🎉 Traitement terminé !');

        return Command::SUCCESS;
    }
}
