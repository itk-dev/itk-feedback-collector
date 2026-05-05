<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260501091343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback ADD website_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445818F45C82 FOREIGN KEY (website_id) REFERENCES website (id)');
        $this->addSql('CREATE INDEX IDX_D229445818F45C82 ON feedback (website_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D229445818F45C82');
        $this->addSql('DROP INDEX IDX_D229445818F45C82 ON feedback');
        $this->addSql('ALTER TABLE feedback DROP website_id');
    }
}
