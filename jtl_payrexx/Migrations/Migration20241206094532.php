<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20241206094532 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            "ALTER TABLE `plugin_jtl_payrexx_payments` 
             ADD `order_hash` VARCHAR(100) NULL AFTER `gateway_id`;"
        );
    }

    public function down()
    {
        $this->execute(
            "ALTER TABLE `plugin_jtl_payrexx_payments` 
             DROP COLUMN `order_hash`;"
        );
    }
}
