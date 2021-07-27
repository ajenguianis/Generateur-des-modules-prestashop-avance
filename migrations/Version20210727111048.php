<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210727111048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE table_mapping (id INT AUTO_INCREMENT NOT NULL, table_name VARCHAR(255) NOT NULL, has_shop_table TINYINT(1) NOT NULL, has_lang_table TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE ps_hook');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ps_hook (id_hook INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(64) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, title VARCHAR(64) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, description TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, position TINYINT(1) DEFAULT \'1\' NOT NULL, UNIQUE INDEX hook_name (name), PRIMARY KEY(id_hook)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE table_mapping');
    }
}
