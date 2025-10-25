<?php

namespace App\MessageHandler;

use App\Message\UserDataExportMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class UserDataExportMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
    ) {
    }    public function __invoke(UserDataExportMessage $message): void
    {
        try {
            $csvFilePath = $message->getCsvFilePath();

            if (!file_exists($csvFilePath)) {
                throw new \Exception("Le fichier CSV n'existe pas : " . $csvFilePath);
            }

            $filename = basename($csvFilePath);

            // Créer l'email avec pièce jointe
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($this->fromEmail)
                ->bcc('didierblais9@gmail.com')
                ->subject('Export des données UserData - ' . date('d/m/Y H:i'))
                ->html($this->getEmailBody($message->getRecordsCount()))
                ->attachFromPath($csvFilePath, $filename, 'text/csv');

            $this->mailer->send($email);

            $this->logger->info('Export UserData envoyé par email', [
                'export_id' => $message->getExportId(),
                'records_count' => $message->getRecordsCount(),
                'file' => $filename
            ]);

            // Supprimer le fichier après envoi pour des raisons de sécurité
            if (file_exists($csvFilePath)) {
                unlink($csvFilePath);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'export UserData', [
                'export_id' => $message->getExportId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function getEmailBody(int $recordsCount): string
    {
        return sprintf('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .info-box { background-color: #fff; border-left: 4px solid #0066cc; padding: 15px; margin: 15px 0; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Export des données UserData</h1>
                    </div>
                    <div class="content">
                        <p>Bonjour,</p>
                        
                        <p>Vous trouverez ci-joint l\'export des données de la table <strong>Utilisateur</strong>.</p>
                        
                        <div class="info-box">
                            <h3>📊 Informations sur l\'export</h3>
                            <ul>
                                <li><strong>Date :</strong> %s</li>
                                <li><strong>Nombre d\'enregistrements :</strong> %d</li>
                                <li><strong>Format :</strong> CSV (séparateur point-virgule)</li>
                                <li><strong>Encodage :</strong> UTF-8 avec BOM (compatible Excel)</li>
                            </ul>
                        </div>
                        
                        <div class="info-box">
                            <h3>📋 Colonnes incluses</h3>
                            <ul>
                                <li>Email</li>
                                <li>Mot de passe </li>
                            </ul>
                        </div>
                        
                        <div class="warning">
                            <h3>⚠️ Attention - Données sensibles</h3>
                            <p>
                                Ce fichier contient des <strong>mots de passe en clair</strong>.
                                Veillez à le traiter avec la plus grande confidentialité et à le supprimer après consultation.
                            </p>
                        </div>
                        
                        <p>Les données sont triées par <strong>ID décroissant</strong> (les plus récentes en premier).</p>
                    </div>
                    <div class="footer">
                        <p>Cet email a été envoyé automatiquement depuis le système administratif.</p>
                        <p>Export généré le %s</p>
                    </div>
                </div>
            </body>
            </html>
        ',
            date('d/m/Y à H:i:s'),
            $recordsCount,
            date('d/m/Y à H:i:s')
        );
    }
}
