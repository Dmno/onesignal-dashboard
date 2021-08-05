<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210607091648 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE notification_stats (id INT AUTO_INCREMENT NOT NULL, notification_id INT DEFAULT NULL, total_receivers INT DEFAULT NULL, total_conversions INT DEFAULT NULL, first_check_receivers INT DEFAULT NULL, first_check_conversions INT DEFAULT NULL, last_check_receivers INT DEFAULT NULL, last_check_conversions INT DEFAULT NULL, last_check_date DATETIME DEFAULT NULL, check_count INT DEFAULT NULL, UNIQUE INDEX UNIQ_91F38253EF1A9D84 (notification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification_stats ADD CONSTRAINT FK_91F38253EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE notification_stats');
    }
}
