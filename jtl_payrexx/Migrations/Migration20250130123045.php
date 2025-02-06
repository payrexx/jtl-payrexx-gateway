<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20250130123045 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` CHANGE `order_id` `order_id` INT NULL;"
        );
    }

    public function down()
    {
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` CHANGE `order_id` `order_id` INT NOT NULL;"
        );
    }
}
