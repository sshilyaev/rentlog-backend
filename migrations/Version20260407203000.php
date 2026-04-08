<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создает таблицы properties и property_members для первого продуктового среза.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE properties (id UUID NOT NULL, title VARCHAR(255) NOT NULL, type_code VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, address VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, metadata JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE property_members (id UUID NOT NULL, property_id UUID NOT NULL, user_id UUID DEFAULT NULL, role VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, full_name VARCHAR(255) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BDCC4A7C549213EC ON property_members (property_id)');
        $this->addSql('CREATE INDEX IDX_BDCC4A7CA76ED395 ON property_members (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_property_members_user ON property_members (property_id, user_id) WHERE user_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_property_members_email ON property_members (property_id, email) WHERE email IS NOT NULL');
        $this->addSql('ALTER TABLE property_members ADD CONSTRAINT FK_BDCC4A7C549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE property_members ADD CONSTRAINT FK_BDCC4A7CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_members');
        $this->addSql('DROP TABLE properties');
    }
}
