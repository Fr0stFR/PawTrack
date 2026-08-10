<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606105938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE breed ADD animal_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE breed ADD CONSTRAINT FK_F8AF884F4A93E3A9 FOREIGN KEY (animal_type_id) REFERENCES animal_type (id)');
        $this->addSql('CREATE INDEX IDX_F8AF884F4A93E3A9 ON breed (animal_type_id)');
        $this->addSql('ALTER TABLE medical_plan ADD last_executed_at DATETIME DEFAULT NULL, ADD animal_id INT NOT NULL');
        $this->addSql('ALTER TABLE medical_plan ADD CONSTRAINT FK_8EA9F1558E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('CREATE INDEX IDX_8EA9F1558E962C16 ON medical_plan (animal_id)');
        $this->addSql('ALTER TABLE reminder ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F40A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_40374F40A76ED395 ON reminder (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE breed DROP FOREIGN KEY FK_F8AF884F4A93E3A9');
        $this->addSql('DROP INDEX IDX_F8AF884F4A93E3A9 ON breed');
        $this->addSql('ALTER TABLE breed DROP animal_type_id');
        $this->addSql('ALTER TABLE medical_plan DROP FOREIGN KEY FK_8EA9F1558E962C16');
        $this->addSql('DROP INDEX IDX_8EA9F1558E962C16 ON medical_plan');
        $this->addSql('ALTER TABLE medical_plan DROP last_executed_at, DROP animal_id');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F40A76ED395');
        $this->addSql('DROP INDEX IDX_40374F40A76ED395 ON reminder');
        $this->addSql('ALTER TABLE reminder DROP user_id');
    }
}
