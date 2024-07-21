<?php
namespace verbb\postie\migrations;

use craft\db\Migration;

class m240629_000000_response_columns extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->alterColumn('{{%postie_shipments}}', 'response', $this->longText());
        $this->alterColumn('{{%postie_rates}}', 'response', $this->longText());
        
        return true;
    }

    public function safeDown(): bool
    {
        echo "m240629_000000_response_columns cannot be reverted.\n";
        return false;
    }
}
