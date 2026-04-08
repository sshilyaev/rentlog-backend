<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создает таблицу invitations для кодов приглашения жильцов.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invitations (id UUID NOT NULL, property_id UUID NOT NULL, property_member_id UUID NOT NULL, created_by_id UUID NOT NULL, code VARCHAR(64) NOT NULL, target_email VARCHAR(180) DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, claimed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INVITATIONS_CODE ON invitations (code)');
        $this->addSql('CREATE INDEX IDX_7C49D6B7549213EC ON invitations (property_id)');
        $this->addSql('CREATE INDEX IDX_7C49D6B773BEC5D9 ON invitations (property_member_id)');
        $this->addSql('CREATE INDEX IDX_7C49D6B7B03A8386 ON invitations (created_by_id)');
        $this->addSql('ALTER TABLE invitations ADD CONSTRAINT FK_7C49D6B7549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitations ADD CONSTRAINT FK_7C49D6B773BEC5D9 FOREIGN KEY (property_member_id) REFERENCES property_members (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitations ADD CONSTRAINT FK_7C49D6B7B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invitations');
    }
}
