<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201026151815 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE account (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE app (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, app_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, total_users INT NOT NULL, subscribed_users INT NOT NULL, auth_key VARCHAR(255) NOT NULL, increase INT NOT NULL, last_check DATETIME NOT NULL, INDEX IDX_C96E70CF9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, INDEX IDX_1F1512DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, short VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE icon (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_659429DBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_C53D045FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, image_id INT DEFAULT NULL, icon_id INT DEFAULT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, url VARCHAR(255) DEFAULT NULL, saved TINYINT(1) NOT NULL, sends INT NOT NULL, last_sent DATETIME DEFAULT NULL, original VARCHAR(255) DEFAULT NULL, INDEX IDX_BF5476CAF92F3E70 (country_id), INDEX IDX_BF5476CA3DA5256D (image_id), INDEX IDX_BF5476CA54B9D732 (icon_id), INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification_campaign (notification_id INT NOT NULL, campaign_id INT NOT NULL, INDEX IDX_4A052A78EF1A9D84 (notification_id), INDEX IDX_4A052A78F639F774 (campaign_id), PRIMARY KEY(notification_id, campaign_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification_data (id INT AUTO_INCREMENT NOT NULL, notification_id INT NOT NULL, app_id INT NOT NULL, sent_notification_id VARCHAR(255) NOT NULL, INDEX IDX_15CFB859EF1A9D84 (notification_id), INDEX IDX_15CFB8597987212D (app_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, notification_id INT DEFAULT NULL, delivery VARCHAR(255) NOT NULL, date VARCHAR(255) DEFAULT NULL, optimisation VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_5A3811FBEF1A9D84 (notification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app ADD CONSTRAINT FK_C96E70CF9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE icon ADD CONSTRAINT FK_659429DBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA3DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA54B9D732 FOREIGN KEY (icon_id) REFERENCES icon (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification_campaign ADD CONSTRAINT FK_4A052A78EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_campaign ADD CONSTRAINT FK_4A052A78F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_data ADD CONSTRAINT FK_15CFB859EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id)');
        $this->addSql('ALTER TABLE notification_data ADD CONSTRAINT FK_15CFB8597987212D FOREIGN KEY (app_id) REFERENCES app (id)');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FBEF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE app DROP FOREIGN KEY FK_C96E70CF9B6B5FBA');
        $this->addSql('ALTER TABLE notification_data DROP FOREIGN KEY FK_15CFB8597987212D');
        $this->addSql('ALTER TABLE notification_campaign DROP FOREIGN KEY FK_4A052A78F639F774');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF92F3E70');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA54B9D732');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA3DA5256D');
        $this->addSql('ALTER TABLE notification_campaign DROP FOREIGN KEY FK_4A052A78EF1A9D84');
        $this->addSql('ALTER TABLE notification_data DROP FOREIGN KEY FK_15CFB859EF1A9D84');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FBEF1A9D84');
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DDA76ED395');
        $this->addSql('ALTER TABLE icon DROP FOREIGN KEY FK_659429DBA76ED395');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE app');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE icon');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notification_campaign');
        $this->addSql('DROP TABLE notification_data');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE user');
    }
}
