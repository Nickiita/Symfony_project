<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250405055711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deal_log ADD sell_portfolio_id INT NOT NULL, ADD buy_portfolio_id INT NOT NULL');
        $this->addSql('ALTER TABLE deal_log ADD CONSTRAINT FK_9477FAFAF07EB4B0 FOREIGN KEY (sell_portfolio_id) REFERENCES portfolio (id)');
        $this->addSql('ALTER TABLE deal_log ADD CONSTRAINT FK_9477FAFACFFCB9FB FOREIGN KEY (buy_portfolio_id) REFERENCES portfolio (id)');
        $this->addSql('CREATE INDEX IDX_9477FAFAF07EB4B0 ON deal_log (sell_portfolio_id)');
        $this->addSql('CREATE INDEX IDX_9477FAFACFFCB9FB ON deal_log (buy_portfolio_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deal_log DROP FOREIGN KEY FK_9477FAFAF07EB4B0');
        $this->addSql('ALTER TABLE deal_log DROP FOREIGN KEY FK_9477FAFACFFCB9FB');
        $this->addSql('DROP INDEX IDX_9477FAFAF07EB4B0 ON deal_log');
        $this->addSql('DROP INDEX IDX_9477FAFACFFCB9FB ON deal_log');
        $this->addSql('ALTER TABLE deal_log DROP sell_portfolio_id, DROP buy_portfolio_id');
    }
}
