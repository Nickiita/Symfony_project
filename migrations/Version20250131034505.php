<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131034505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application ADD portfolio_id INT NOT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1B96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC1B96B5643 ON application (portfolio_id)');
        $this->addSql('ALTER TABLE depositary ADD frozen_quantity INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD frozen_balance DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1B96B5643');
        $this->addSql('DROP INDEX IDX_A45BDDC1B96B5643 ON application');
        $this->addSql('ALTER TABLE application DROP portfolio_id');
        $this->addSql('ALTER TABLE depositary DROP frozen_quantity');
        $this->addSql('ALTER TABLE portfolio DROP frozen_balance');
    }
}
