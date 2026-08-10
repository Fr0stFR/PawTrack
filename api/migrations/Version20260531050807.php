<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531050807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, birthdate DATE NOT NULL, gender VARCHAR(1) NOT NULL, photo VARCHAR(255) DEFAULT NULL, animal_type_id INT NOT NULL, breed_id INT DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_6AAB231F4A93E3A9 (animal_type_id), INDEX IDX_6AAB231FA8B4A30F (breed_id), INDEX IDX_6AAB231F7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE animal_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE breed (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE medical_event (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date DATETIME NOT NULL, is_done TINYINT NOT NULL, done_at DATETIME NOT NULL, medical_type_id INT NOT NULL, animal_id INT NOT NULL, created_by_id INT NOT NULL, veterinarian_id INT DEFAULT NULL, medical_plan_id INT DEFAULT NULL, INDEX IDX_E4851F7834E6C5D (medical_type_id), INDEX IDX_E4851F78E962C16 (animal_id), INDEX IDX_E4851F7B03A8386 (created_by_id), INDEX IDX_E4851F7804C8213 (veterinarian_id), INDEX IDX_E4851F7AE9BE255 (medical_plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE medical_plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, frequency VARCHAR(255) NOT NULL, frequency_value INT NOT NULL, medical_type_id INT NOT NULL, INDEX IDX_8EA9F155834E6C5D (medical_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE medical_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, medical_event_id INT NOT NULL, INDEX IDX_40374F40D6D05DC (medical_event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE weight_record (id INT AUTO_INCREMENT NOT NULL, weight INT NOT NULL, recorded_at DATETIME NOT NULL, animal_id INT NOT NULL, INDEX IDX_506A8B488E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F4A93E3A9 FOREIGN KEY (animal_type_id) REFERENCES animal_type (id)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231FA8B4A30F FOREIGN KEY (breed_id) REFERENCES breed (id)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F7834E6C5D FOREIGN KEY (medical_type_id) REFERENCES medical_type (id)');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F78E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F7B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F7804C8213 FOREIGN KEY (veterinarian_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE medical_event ADD CONSTRAINT FK_E4851F7AE9BE255 FOREIGN KEY (medical_plan_id) REFERENCES medical_plan (id)');
        $this->addSql('ALTER TABLE medical_plan ADD CONSTRAINT FK_8EA9F155834E6C5D FOREIGN KEY (medical_type_id) REFERENCES medical_type (id)');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F40D6D05DC FOREIGN KEY (medical_event_id) REFERENCES medical_event (id)');
        $this->addSql('ALTER TABLE weight_record ADD CONSTRAINT FK_506A8B488E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F4A93E3A9');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231FA8B4A30F');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F7E3C61F9');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F7834E6C5D');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F78E962C16');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F7B03A8386');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F7804C8213');
        $this->addSql('ALTER TABLE medical_event DROP FOREIGN KEY FK_E4851F7AE9BE255');
        $this->addSql('ALTER TABLE medical_plan DROP FOREIGN KEY FK_8EA9F155834E6C5D');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F40D6D05DC');
        $this->addSql('ALTER TABLE weight_record DROP FOREIGN KEY FK_506A8B488E962C16');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE animal_type');
        $this->addSql('DROP TABLE breed');
        $this->addSql('DROP TABLE medical_event');
        $this->addSql('DROP TABLE medical_plan');
        $this->addSql('DROP TABLE medical_type');
        $this->addSql('DROP TABLE reminder');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE weight_record');
    }
}
