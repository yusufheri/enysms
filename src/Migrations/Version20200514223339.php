<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200514223339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE favorite ADD groupe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED97A45358C FOREIGN KEY (groupe_id) REFERENCES `group` (id)');
        $this->addSql('CREATE INDEX IDX_68C58ED97A45358C ON favorite (groupe_id)');
        $this->addSql('ALTER TABLE message ADD person_id INT NOT NULL, ADD favorite_id INT NOT NULL, DROP content');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FAA17481D FOREIGN KEY (favorite_id) REFERENCES favorite (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F217BBB47 ON message (person_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FAA17481D ON message (favorite_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED97A45358C');
        $this->addSql('DROP INDEX IDX_68C58ED97A45358C ON favorite');
        $this->addSql('ALTER TABLE favorite DROP groupe_id');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F217BBB47');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FAA17481D');
        $this->addSql('DROP INDEX IDX_B6BD307F217BBB47 ON message');
        $this->addSql('DROP INDEX IDX_B6BD307FAA17481D ON message');
        $this->addSql('ALTER TABLE message ADD content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP person_id, DROP favorite_id');
    }
}
