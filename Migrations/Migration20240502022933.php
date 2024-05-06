<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20240502022933 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `payrexx_payments` (
		    `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` varchar(64) NOT NULL,
		    `gateway_id` varchar(64) NOT NULL,
		    `created_at` datetime NOT NULL,
		    PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `payrexx_payments`');
    }
}