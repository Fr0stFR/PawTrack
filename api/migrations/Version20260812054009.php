<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812054009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le code machine sur medical_type et sème les types de référence.';
    }

    public function up(Schema $schema): void
    {
        // Détour par NULL : une colonne ajoutée directement en NOT NULL vaudrait
        // '' sur toutes les lignes existantes, et l'index unique échouerait.
        $this->addSql('ALTER TABLE medical_type ADD code VARCHAR(50) DEFAULT NULL');

        // Les anciens types sont recyclés plutôt que supprimés : des événements
        // et des automatisations y sont accrochés par clé étrangère.
        $this->addSql("UPDATE medical_type SET code = 'antiparasitic', name = 'Prise Antiparasitaire' WHERE name = 'Vermifuge'");
        $this->addSql("UPDATE medical_type SET code = 'treatment', name = 'Prise Traitement' WHERE name = 'Vaccin'");
        $this->addSql("UPDATE medical_type SET code = 'appointment' WHERE name = 'Rendez-vous vétérinaire'");

        // Filet pour une base dont les libellés auraient déjà divergé : sans lui,
        // une ligne inattendue ferait échouer le passage en NOT NULL.
        $this->addSql("UPDATE medical_type SET code = CONCAT('type_', id) WHERE code IS NULL");

        $this->addSql('ALTER TABLE medical_type MODIFY code VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DF2DFD0177153098 ON medical_type (code)');

        // Données de référence : le code en dépend, elles doivent exister dans
        // tous les environnements. IGNORE laisse intactes les lignes déjà là.
        $this->addSql("INSERT IGNORE INTO medical_type (code, name) VALUES
            ('antiparasitic', 'Prise Antiparasitaire'),
            ('treatment', 'Prise Traitement'),
            ('appointment', 'Rendez-vous vétérinaire')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DF2DFD0177153098 ON medical_type');
        $this->addSql('ALTER TABLE medical_type DROP code');
    }
}
