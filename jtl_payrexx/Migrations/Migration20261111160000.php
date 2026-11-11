<?php

namespace Plugin\jtl_payrexx\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20261111160000 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` ADD `payment_session_id` VARCHAR(64) NULL DEFAULT NULL;"
        );
    }

    public function down()
    {
        $this->execute(
            "ALTER TABLE
                `plugin_jtl_payrexx_payments` DROP `payment_session_id`;"
        );
    }
}
