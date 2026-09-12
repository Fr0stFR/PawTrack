<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830052113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Référentiel Protection (valences de vaccin, antiparasitaires) + lien depuis MedicalEvent et MedicalPlan';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE protection (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(50) NOT NULL, default_frequency VARCHAR(10) NOT NULL, default_frequency_value INT NOT NULL, UNIQUE INDEX UNIQ_B7E52FB577153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE protection_animal_type (protection_id INT NOT NULL, animal_type_id INT NOT NULL, INDEX IDX_91A99A6767A8D7E6 (protection_id), INDEX IDX_91A99A674A93E3A9 (animal_type_id), PRIMARY KEY (protection_id, animal_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE protection_animal_type ADD CONSTRAINT FK_91A99A6767A8D7E6 FOREIGN KEY (protection_id) REFERENCES protection (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE protection_animal_type ADD CONSTRAINT FK_91A99A674A93E3A9 FOREIGN KEY (animal_type_id) REFERENCES animal_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medical_event ADD protection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F767A8D7E6 FOREIGN KEY (protection_id) REFERENCES protection (id)');
        $this->addSql('CREATE INDEX IDX_E4851F767A8D7E6 ON medical_event (protection_id)');
        $this->addSql('ALTER TABLE medical_plan ADD protection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_plan ADD CONSTRAINT FK_8EA9F15567A8D7E6 FOREIGN KEY (protection_id) REFERENCES protection (id)');
        $this->addSql('CREATE INDEX IDX_8EA9F15567A8D7E6 ON medical_plan (protection_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE protection_animal_type DROP FOREIGN KEY FK_91A99A6767A8D7E6');
        $this->addSql('ALTER TABLE protection_animal_type DROP FOREIGN KEY FK_91A99A674A93E3A9');
        $this->addSql('DROP TABLE protection');
        $this->addSql('DROP TABLE protection_animal_type');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F767A8D7E6');
        $this->addSql('DROP INDEX IDX_E4851F767A8D7E6 ON medical_event');
        $this->addSql('ALTER TABLE medical_event DROP protection_id');
        $this->addSql('ALTER TABLE medical_plan DROP FOREIGN KEY FK_8EA9F15567A8D7E6');
        $this->addSql('DROP INDEX IDX_8EA9F15567A8D7E6 ON medical_plan');
        $this->addSql('ALTER TABLE medical_plan DROP protection_id');
    }
}
