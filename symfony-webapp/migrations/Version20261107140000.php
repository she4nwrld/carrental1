<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Shift Car Rental schema (user, car, booking, promo, maintenance_record, review, message)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_active INT DEFAULT 1 NOT NULL, created_at VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS car (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(40) NOT NULL, price INT NOT NULL, gear VARCHAR(20) NOT NULL, seats INT NOT NULL, doors INT NOT NULL, bag_large INT NOT NULL, bag_small INT NOT NULL, kids INT NOT NULL, aircon TINYINT(1) NOT NULL, img VARCHAR(150) NOT NULL, available TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS booking (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, car_id INT NOT NULL, car_name VARCHAR(100) NOT NULL, car_img VARCHAR(150) NOT NULL, car_type VARCHAR(40) NOT NULL, full_name VARCHAR(100) NOT NULL, email VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, pickup_date VARCHAR(20) NOT NULL, return_date VARCHAR(20) NOT NULL, pickup_location VARCHAR(80) NOT NULL, return_location VARCHAR(80) NOT NULL, driver_age VARCHAR(30) NOT NULL, days INT NOT NULL, delivery TINYINT(1) NOT NULL, discount_code VARCHAR(40) DEFAULT NULL, subtotal INT NOT NULL, discount INT NOT NULL, total INT NOT NULL, status VARCHAR(20) NOT NULL, created_at VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS promo (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) DEFAULT NULL, tag VARCHAR(40) NOT NULL, title VARCHAR(120) NOT NULL, blurb LONGTEXT NOT NULL, percent INT NOT NULL, free_days INT NOT NULL, min_days INT NOT NULL, min_advance_days INT NOT NULL, stackable INT NOT NULL, expires_at VARCHAR(20) DEFAULT NULL, sort_order INT NOT NULL, active INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS maintenance_record (id INT AUTO_INCREMENT NOT NULL, car_id INT NOT NULL, car_name VARCHAR(100) NOT NULL, car_type VARCHAR(40) NOT NULL, car_img VARCHAR(150) NOT NULL, start_date VARCHAR(20) NOT NULL, end_date VARCHAR(20) NOT NULL, cost INT NOT NULL, description VARCHAR(255) NOT NULL, resolved TINYINT(1) NOT NULL, created_at VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS review (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, full_name VARCHAR(100) NOT NULL, email VARCHAR(120) NOT NULL, rating INT NOT NULL, review_text LONGTEXT NOT NULL, approved TINYINT(1) NOT NULL, created_at VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS message (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, account_name VARCHAR(100) DEFAULT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(120) NOT NULL, phone VARCHAR(40) DEFAULT NULL, body LONGTEXT NOT NULL, is_read INT NOT NULL, created_at VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS review');
        $this->addSql('DROP TABLE IF EXISTS maintenance_record');
        $this->addSql('DROP TABLE IF EXISTS promo');
        $this->addSql('DROP TABLE IF EXISTS booking');
        $this->addSql('DROP TABLE IF EXISTS car');
        $this->addSql('DROP TABLE IF EXISTS `user`');
    }
}
