<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20261111160000 extends Migration implements IMigration
{
    public function up()
    {
        // The webhook looks the gateway up by gateway and order hash, or by order id or order hash
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` ADD `payment_session_id` VARCHAR(64) NULL DEFAULT NULL,
                ADD KEY `gateway_id_order_hash` (`gateway_id`, `order_hash`),
                ADD KEY `order_hash` (`order_hash`),
                ADD KEY `order_id` (`order_id`);"
        );
    }

    public function down()
    {
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` DROP `payment_session_id`,
                DROP KEY `gateway_id_order_hash`,
                DROP KEY `order_hash`,
                DROP KEY `order_id`;"
        );
    }
}
