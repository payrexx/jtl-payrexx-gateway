<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20240502022933 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `plugin_jtl_payrexx_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `gateway_id` int(11) NOT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `plugin_jtl_payrexx_payments`');
    }
}
