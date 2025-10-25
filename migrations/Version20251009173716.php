<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009173716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scheduled_email (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, created_by_id INT DEFAULT NULL, template_type VARCHAR(255) NOT NULL, reasons JSON DEFAULT NULL, scheduled_for DATETIME DEFAULT NULL, sent_at DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, locale VARCHAR(5) NOT NULL, custom_message LONGTEXT DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A5EF255FE92F8F78 (recipient_id), INDEX IDX_A5EF255FB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scheduled_email ADD CONSTRAINT FK_A5EF255FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE scheduled_email ADD CONSTRAINT FK_A5EF255FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scheduled_email DROP FOREIGN KEY FK_A5EF255FE92F8F78');
        $this->addSql('ALTER TABLE scheduled_email DROP FOREIGN KEY FK_A5EF255FB03A8386');
        $this->addSql('DROP TABLE scheduled_email');
    }
}
