<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241116070635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE depositary (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, portfolio_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_7CD08B68DCD6110 (stock_id), INDEX IDX_7CD08B68B96B5643 (portfolio_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE depositary ADD CONSTRAINT FK_7CD08B68DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE depositary ADD CONSTRAINT FK_7CD08B68B96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE depositary DROP FOREIGN KEY FK_7CD08B68DCD6110');
        $this->addSql('ALTER TABLE depositary DROP FOREIGN KEY FK_7CD08B68B96B5643');
        $this->addSql('DROP TABLE depositary');
    }
}
