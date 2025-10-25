<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011164233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE custom_email (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, created_by_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, attachments JSON DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, INDEX IDX_249F841DE92F8F78 (recipient_id), INDEX IDX_249F841DB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_email ADD CONSTRAINT FK_249F841DE92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE custom_email ADD CONSTRAINT FK_249F841DB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_email DROP FOREIGN KEY FK_249F841DE92F8F78');
        $this->addSql('ALTER TABLE custom_email DROP FOREIGN KEY FK_249F841DB03A8386');
        $this->addSql('DROP TABLE custom_email');
    }
}
