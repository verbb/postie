<?php
namespace verbb\postie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class Provider extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%postie_providers}}';
    }
}
