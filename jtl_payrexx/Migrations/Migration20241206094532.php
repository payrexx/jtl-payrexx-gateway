<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20241206094532 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            "ALTER TABLE `plugin_jtl_payments` 
             ADD `order_hash` VARCHAR(100) NULL AFTER `gateway_id`;"
        );

        $this->execute(
            "ALTER TABLE `plugin_jtl_payments` 
             CHANGE `order_id` `order_id` INT NULL;"
        );
    }

    public function down()
    {
        $this->execute(
            "ALTER TABLE `plugin_jtl_payments` 
             DROP COLUMN `order_hash`;"
        );

        $this->execute(
            "ALTER TABLE `plugin_jtl_payments` 
             CHANGE `order_id` `order_id` INT NOT NULL;"
        );
    }
}