<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Auth: refresh tokens, email verification, password reset; meters.unit normalized for MeterUnit enum.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_users_email_verification_token ON users (email_verification_token) WHERE email_verification_token IS NOT NULL');
        $this->addSql('ALTER TABLE users ADD password_reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD password_reset_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_users_password_reset_token ON users (password_reset_token) WHERE password_reset_token IS NOT NULL');

        $this->addSql('CREATE TABLE refresh_tokens (id UUID NOT NULL, user_id UUID NOT NULL, token_hash VARCHAR(128) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_refresh_tokens_token_hash ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX IDX_refresh_tokens_user_id ON refresh_tokens (user_id)');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_refresh_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("UPDATE meters SET unit = 'm3' WHERE unit IN ('м³', 'M3', 'm³', 'куб.м', 'куб м') OR unit = ''");
        $this->addSql("UPDATE meters SET unit = 'kwh' WHERE unit ILIKE '%квт%' OR unit ILIKE '%kwh%'");
        $this->addSql("UPDATE meters SET unit = 'gcal' WHERE unit ILIKE '%гкал%' OR unit ILIKE '%gcal%'");
        $this->addSql("UPDATE meters SET unit = 'l' WHERE unit IN ('л', 'L', 'л.')");
        $this->addSql("UPDATE meters SET unit = 'm2' WHERE unit IN ('м²', 'm2', 'м2')");
        $this->addSql("UPDATE meters SET unit = 'pc' WHERE unit ILIKE '%шт%'");
        $this->addSql("UPDATE meters SET unit = 'm3' WHERE unit NOT IN ('m3', 'l', 'kwh', 'gcal', 'm2', 'pc')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP INDEX UNIQ_users_email_verification_token');
        $this->addSql('DROP INDEX UNIQ_users_password_reset_token');
        $this->addSql('ALTER TABLE users DROP email_verified_at');
        $this->addSql('ALTER TABLE users DROP email_verification_token');
        $this->addSql('ALTER TABLE users DROP password_reset_token');
        $this->addSql('ALTER TABLE users DROP password_reset_expires_at');
    }
}
