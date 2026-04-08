<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создает таблицы rent_terms, meters, meter_readings, billing_parameters и tariff_periods.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rent_terms (id UUID NOT NULL, property_id UUID NOT NULL, property_member_id UUID DEFAULT NULL, base_rent_amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, billing_day SMALLINT NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notes TEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8B521706549213EC ON rent_terms (property_id)');
        $this->addSql('CREATE INDEX IDX_8B5217068D93D649 ON rent_terms (property_member_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rent_terms_property_base ON rent_terms (property_id) WHERE property_member_id IS NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_rent_terms_property_member ON rent_terms (property_id, property_member_id) WHERE property_member_id IS NOT NULL');
        $this->addSql('ALTER TABLE rent_terms ADD CONSTRAINT FK_8B521706549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE rent_terms ADD CONSTRAINT FK_8B5217068D93D649 FOREIGN KEY (property_member_id) REFERENCES property_members (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE meters (id UUID NOT NULL, property_id UUID NOT NULL, code VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, unit VARCHAR(50) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5E193C55549213EC ON meters (property_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_meters_property_code ON meters (property_id, code)');
        $this->addSql('ALTER TABLE meters ADD CONSTRAINT FK_5E193C55549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE billing_parameters (id UUID NOT NULL, property_id UUID NOT NULL, meter_id UUID DEFAULT NULL, code VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(50) NOT NULL, source_type VARCHAR(50) NOT NULL, unit VARCHAR(50) DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_819D3E4E549213EC ON billing_parameters (property_id)');
        $this->addSql('CREATE INDEX IDX_819D3E4E4779193A ON billing_parameters (meter_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_billing_parameters_property_code ON billing_parameters (property_id, code)');
        $this->addSql('ALTER TABLE billing_parameters ADD CONSTRAINT FK_819D3E4E549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE billing_parameters ADD CONSTRAINT FK_819D3E4E4779193A FOREIGN KEY (meter_id) REFERENCES meters (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE tariff_periods (id UUID NOT NULL, billing_parameter_id UUID NOT NULL, pricing_type VARCHAR(50) NOT NULL, price NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, effective_from TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, effective_to TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D4D9E66746A1E09 ON tariff_periods (billing_parameter_id)');
        $this->addSql('ALTER TABLE tariff_periods ADD CONSTRAINT FK_8D4D9E66746A1E09 FOREIGN KEY (billing_parameter_id) REFERENCES billing_parameters (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE meter_readings (id UUID NOT NULL, meter_id UUID NOT NULL, recorded_by_user_id UUID NOT NULL, type VARCHAR(50) NOT NULL, billing_year SMALLINT DEFAULT NULL, billing_month SMALLINT DEFAULT NULL, value NUMERIC(12, 3) NOT NULL, comment TEXT DEFAULT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7AB0C0BF4779193A ON meter_readings (meter_id)');
        $this->addSql('CREATE INDEX IDX_7AB0C0BF3B0AA204 ON meter_readings (recorded_by_user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_meter_initial_reading ON meter_readings (meter_id) WHERE type = \'initial\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_meter_monthly_reading ON meter_readings (meter_id, billing_year, billing_month) WHERE type = \'monthly\'');
        $this->addSql('ALTER TABLE meter_readings ADD CONSTRAINT FK_7AB0C0BF4779193A FOREIGN KEY (meter_id) REFERENCES meters (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meter_readings ADD CONSTRAINT FK_7AB0C0BF3B0AA204 FOREIGN KEY (recorded_by_user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE meter_readings');
        $this->addSql('DROP TABLE tariff_periods');
        $this->addSql('DROP TABLE billing_parameters');
        $this->addSql('DROP TABLE meters');
        $this->addSql('DROP TABLE rent_terms');
    }
}
