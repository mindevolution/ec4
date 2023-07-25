<?php declare(strict_types=1);

namespace Plugin\StripePaymentGateway\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201214131315 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $Table = $schema->getTable('plg_stripe_config');
        if(!$Table->hasColumn('prod_detail_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config ADD prod_detail_ga_enable INT DEFAULT 0');
        }
        if(!$Table->hasColumn('cart_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config ADD cart_ga_enable INT DEFAULT 0');
        }
        if(!$Table->hasColumn('checkout_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config ADD checkout_ga_enable INT DEFAULT 0');
        }
        if($Table->hasColumn('ga_pay_enabled')){
            $this->addSql('ALTER TABLE plg_stripe_config DROP ga_pay_enabled');
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $Table = $schema->getTable('plg_stripe_config');
        if($Table->hasColumn('prod_detail_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config DROP prod_detail_ga_enable');
        }
        if($Table->hasColumn('cart_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config DROP cart_ga_enable');
        }
        if($Table->hasColumn('checkout_ga_enable')){
            $this->addSql('ALTER TABLE plg_stripe_config DROP checkout_ga_enable');
        }

        if(!$Table->hasColumn('ga_pay_enabled')){
            $this->addSql('ALTER TABLE plg_stripe_config ADD ga_pay_enabled INT DEFAULT 0');
        }
        
    }
}
