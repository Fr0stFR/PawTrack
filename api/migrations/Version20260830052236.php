<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Données de référence : espèces, types d'acte, protections.
 */
final class Version20260830052236 extends AbstractMigration
{
    /**
     * @var list<array{string, string, ?string, string, string, int, list<string>}>
     */
    private const PROTECTIONS = [
        // code,        name,                 description,                                      category,         freq,    value, espèces
        ['chp',         'Vaccin CHP',         'Carré, hépatite de Rubarth, parvovirose',        'vaccine',        'year',  1,     ['Chien']],
        ['pi',          'Vaccin Pi',          'Parainfluenza (toux du chenil)',                 'vaccine',        'year',  1,     ['Chien']],
        ['lepto',       'Vaccin L',           'Leptospirose',                                   'vaccine',        'year',  1,     ['Chien']],
        ['tcp',         'Vaccin TCP',         'Typhus, calicivirose, rhinotrachéite',           'vaccine',        'year',  1,     ['Chat']],
        ['felv',        'Vaccin FeLV',        'Leucose féline',                                 'vaccine',        'year',  1,     ['Chat']],
        ['rabies',      'Vaccin rage',        'Rage — obligatoire pour voyager',                'vaccine',        'year',  1,     ['Chien', 'Chat']],
        ['deworming',   'Vermifuge',          'Vers ronds et vers plats',                       'antiparasitic',  'month', 3,     ['Chien', 'Chat']],
        ['flea_tick',   'Antipuces et tiques', 'Puces, tiques',                                 'antiparasitic',  'month', 1,     ['Chien', 'Chat']],
    ];

    public function getDescription(): string
    {
        return 'Données de référence : espèces, types d\'acte, et le référentiel Protection';
    }

    public function up(Schema $schema): void
    {
        foreach (['Chien', 'Chat'] as $name) {
            $this->addSql(
                'INSERT INTO animal_type (name) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM animal_type a WHERE a.name = ?)',
                [$name, $name],
            );
        }

        // --- Types d'acte --------------------------------------------------
        // `code` est unique : INSERT IGNORE suffit à rendre l'opération rejouable.
        // « Vaccination » manquait — un vaccin était rangé sous « Prise
        // Traitement », la même case qu'un antibiotique.
        $medicalTypes = [
            ['treatment', 'Prise Traitement'],
            ['antiparasitic', 'Prise Antiparasitaire'],
            ['appointment', 'Rendez-vous vétérinaire'],
            ['vaccine', 'Vaccination'],
        ];

        foreach ($medicalTypes as [$code, $name]) {
            $this->addSql('INSERT IGNORE INTO medical_type (code, name) VALUES (?, ?)', [$code, $name]);
        }

        // --- Protections ---------------------------------------------------
        foreach (self::PROTECTIONS as [$code, $name, $description, $category, $frequency, $value, $species]) {
            $this->addSql(
                'INSERT IGNORE INTO protection (code, name, description, category, default_frequency, default_frequency_value)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$code, $name, $description, $category, $frequency, $value],
            );

            // La table de jointure se remplit par jointure sur les clés métier :
            foreach ($species as $animalTypeName) {
                $this->addSql(
                    'INSERT IGNORE INTO protection_animal_type (protection_id, animal_type_id)
                     SELECT p.id, a.id FROM protection p, animal_type a
                     WHERE p.code = ? AND a.name = ?',
                    [$code, $animalTypeName],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        // On ne retire que ce qui n'a aucune chance d'être référencé par des
        // données utilisateur. Les espèces et les types d'acte préexistants sont
        // laissés en place : `animal` et `medical_event` pointent dessus, et une
        // migration inverse n'a pas à casser des dossiers.
        foreach (self::PROTECTIONS as [$code]) {
            $this->addSql(
                'DELETE FROM protection_animal_type WHERE protection_id IN (SELECT id FROM protection WHERE code = ?)',
                [$code],
            );
            $this->addSql('DELETE FROM protection WHERE code = ?', [$code]);
        }

        $this->addSql("DELETE FROM medical_type WHERE code = 'vaccine'");
    }
}
