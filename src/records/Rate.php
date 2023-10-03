<?php
namespace verbb\postie\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

use craft\commerce\records\Order;

class Rate extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%postie_rates}}';
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }

    public function getProvider(): ActiveQuery
    {
        return $this->hasOne(Provider::class, ['handle' => 'providerHandle']);
    }
}
