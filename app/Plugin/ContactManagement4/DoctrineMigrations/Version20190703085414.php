<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190703085414 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO plg_contact_management4_contact_status (id, name, sort_no, discriminator_type) VALUES (1, '未対応', 1, 'contactstatus')");
        $this->addSql("INSERT INTO plg_contact_management4_contact_status (id, name, sort_no, discriminator_type) VALUES (2, '対応中', 2, 'contactstatus')");
        $this->addSql("INSERT INTO plg_contact_management4_contact_status (id, name, sort_no, discriminator_type) VALUES (99, '対応完了', 99, 'contactstatus')");
    }

    public function down(Schema $schema): void
    {
    }
}
