<?php

namespace App\Command;

use App\Message\SendScheduledEmailMessage;
use App\Repository\ScheduledEmailRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:scheduled-emails:send',
    description: 'Send scheduled emails that are ready to be sent',
)]
class SendScheduledEmailsCommand extends Command
{
    public function __construct(
        private ScheduledEmailRepository $scheduledEmailRepository,
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find pending immediate emails
        $pendingImmediate = $this->scheduledEmailRepository->findPendingImmediate();
        
        // Find scheduled emails ready to be sent
        $pendingScheduled = $this->scheduledEmailRepository->findPendingScheduled();

        $allPending = array_merge($pendingImmediate, $pendingScheduled);

        if (empty($allPending)) {
            $io->success('Aucun email en attente d\'envoi.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($allPending as $scheduledEmail) {
            try {
                $this->messageBus->dispatch(new SendScheduledEmailMessage($scheduledEmail->getId()));
                $count++;
                
                $io->writeln(sprintf(
                    'Email #%d dispatché pour %s (type: %s)',
                    $scheduledEmail->getId(),
                    $scheduledEmail->getRecipient()->getEmail(),
                    $scheduledEmail->getTemplateType()->getLabel()
                ));
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Erreur lors du dispatch de l\'email #%d: %s',
                    $scheduledEmail->getId(),
                    $e->getMessage()
                ));
            }
        }

        $io->success(sprintf('%d email(s) dispatché(s) pour envoi.', $count));

        return Command::SUCCESS;
    }
}
