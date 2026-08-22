<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute MedicalPlan::startsAt — la date de première échéance saisie par
 * l'utilisateur, distincte de lastExecutedAt qui reste le curseur du système.
 */
final class Version20260819051431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute medical_plan.starts_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE medical_plan ADD starts_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE medical_plan DROP starts_at');
    }
}
