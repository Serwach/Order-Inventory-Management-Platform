<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525141715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invitations (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, token VARCHAR(64) NOT NULL, role VARCHAR(50) NOT NULL, invited_by_user_id VARCHAR(36) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232710AE5F37A13B ON invitations (token)');
        $this->addSql('CREATE INDEX idx_invitation_token ON invitations (token)');
        $this->addSql('CREATE INDEX idx_invitation_email ON invitations (email)');
        $this->addSql('CREATE TABLE order_items (id VARCHAR(36) NOT NULL, product_id VARCHAR(36) NOT NULL, variant_id VARCHAR(36) DEFAULT NULL, sku VARCHAR(50) NOT NULL, product_name VARCHAR(200) NOT NULL, quantity INT NOT NULL, unit_price_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, order_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_order_item_order ON order_items (order_id)');
        $this->addSql('CREATE TABLE orders (version INT DEFAULT 1 NOT NULL, id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, subtotal_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, notes TEXT DEFAULT NULL, shipping_address JSON NOT NULL, placed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, payment_id VARCHAR(36) DEFAULT NULL, cancellation_reason VARCHAR(100) DEFAULT NULL, number VARCHAR(30) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_order_organization_status ON orders (organization_id, status)');
        $this->addSql('CREATE INDEX idx_order_customer ON orders (organization_id, customer_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_order_number ON orders (number)');
        $this->addSql('CREATE TABLE organizations (id VARCHAR(36) NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(63) NOT NULL, plan VARCHAR(50) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_organization_slug ON organizations (slug)');
        $this->addSql('CREATE TABLE product_variants (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, sku VARCHAR(50) NOT NULL, name VARCHAR(200) NOT NULL, price_override INT DEFAULT NULL, currency VARCHAR(3) NOT NULL, attributes JSON NOT NULL, active BOOLEAN NOT NULL, product_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_782839764584665A ON product_variants (product_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_variant_org_sku ON product_variants (organization_id, sku)');
        $this->addSql('CREATE TABLE products (version INT DEFAULT 1 NOT NULL, id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, sku VARCHAR(50) NOT NULL, name VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, base_price INT NOT NULL, currency VARCHAR(3) NOT NULL, category VARCHAR(50) DEFAULT NULL, attributes JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_product_organization ON products (organization_id)');
        $this->addSql('CREATE INDEX idx_product_active ON products (organization_id, active)');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_org_sku ON products (organization_id, sku)');
        $this->addSql('CREATE TABLE stock_entries (version INT DEFAULT 1 NOT NULL, id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, product_id VARCHAR(36) NOT NULL, warehouse_id VARCHAR(36) NOT NULL, on_hand INT NOT NULL, reserved INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_stock_organization ON stock_entries (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_stock_product_warehouse ON stock_entries (product_id, warehouse_id, organization_id)');
        $this->addSql('CREATE TABLE stock_movements (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, delta INT NOT NULL, reason VARCHAR(100) NOT NULL, reference_id VARCHAR(36) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stock_entry_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_movement_stock_entry ON stock_movements (stock_entry_id)');
        $this->addSql('CREATE INDEX idx_movement_organization ON stock_movements (organization_id)');
        $this->addSql('CREATE TABLE stock_reservations (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, quantity INT NOT NULL, order_id VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, stock_entry_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2126982AF5F01D00 ON stock_reservations (stock_entry_id)');
        $this->addSql('CREATE INDEX idx_reservation_order ON stock_reservations (order_id)');
        $this->addSql('CREATE INDEX idx_reservation_organization ON stock_reservations (organization_id)');
        $this->addSql('CREATE TABLE users (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_user_organization ON users (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON users (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT GENERATED BY DEFAULT AS IDENTITY NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C9F5F01D00 FOREIGN KEY (stock_entry_id) REFERENCES stock_entries (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_reservations ADD CONSTRAINT FK_2126982AF5F01D00 FOREIGN KEY (stock_entry_id) REFERENCES stock_entries (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E932C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE product_variants DROP CONSTRAINT FK_782839764584665A');
        $this->addSql('ALTER TABLE stock_movements DROP CONSTRAINT FK_A0BE93C9F5F01D00');
        $this->addSql('ALTER TABLE stock_reservations DROP CONSTRAINT FK_2126982AF5F01D00');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E932C8A3DE');
        $this->addSql('DROP TABLE invitations');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE organizations');
        $this->addSql('DROP TABLE product_variants');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE stock_entries');
        $this->addSql('DROP TABLE stock_movements');
        $this->addSql('DROP TABLE stock_reservations');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
