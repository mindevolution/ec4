<?php declare(strict_types=1);

namespace Plugin\StripePaymentGateway\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201207032429 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $Table = $schema->getTable('dtb_order');
        if(!$Table->hasColumn('temp_intent_id')){
            $this->addSql('ALTER TABLE dtb_order ADD temp_intent_id VARCHAR(255) DEFAULT NULL COLLATE utf8_general_ci');
        }
        if($Table->hasColumn('coupon_id')){
            $this->addSql('ALTER TABLE dtb_order DROP coupon_id;');
        }
        if($Table->hasColumn('coupon_amount')){
            $this->addSql('ALTER TABLE dtb_order DROP coupon_amount;');
        }
        if($Table->hasColumn('coupon_name')){
            $this->addSql('ALTER TABLE dtb_order DROP coupon_name;');
        }
        $Table = $schema->getTable('plg_stripe_config');
        if($Table->hasColumn('coupon_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config DROP coupon_enable');
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
