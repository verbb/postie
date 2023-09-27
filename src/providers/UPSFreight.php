<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\UPSFreight as UPSFreightCarrier;

class UPSFreight extends UPS
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'UPS Freight');
    }

    public static function getCarrierClass(): string
    {
        return UPSFreightCarrier::class;
    }
    

    // Properties
    // =========================================================================

    public ?string $freightClass = null;
    public ?string $freightPackingType = null;


    // Public Methods
    // =========================================================================

    public function getFreightClass(): ?string
    {
        return App::parseEnv($this->freightClass);
    }

    public function getFreightPackingType(): ?string
    {
        return App::parseEnv($this->freightPackingType);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['freightClass', 'freightPackingType'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['freightClass'] = $this->getFreightClass();
        $config['freightPackingType'] = $this->getFreightPackingType();

        return $config;
    }

    public function getFreightPackingTypeOptions(): array
    {
        $options = [];

        $packingTypes = [
            'BAG' => 'Bag',
            'BAL' => 'Bale',
            'BAR' => 'Barrel',
            'BDL' => 'Bundle',
            'BIN' => 'Bin',
            'BOX' => 'Box',
            'BSK' => 'Basket',
            'BUN' => 'Bunch',
            'CAB' => 'Cabinet',
            'CAN' => 'Can',
            'CAR' => 'Carrier',
            'CAS' => 'Case',
            'CBY' => 'CarBoy',
            'CON' => 'Container',
            'CRT' => 'Crate',
            'CSK' => 'Cask',
            'CTN' => 'Carton',
            'CYL' => 'Cylinder',
            'DRM' => 'Drum',
            'LOO' => 'Loose',
            'OTH' => 'Other',
            'PAL' => 'Pail',
            'PCS' => 'Pieces',
            'PKG' => 'Package',
            'PLN' => 'Pipe Line',
            'PLT' => 'Pallet',
            'RCK' => 'Rack',
            'REL' => 'Reel',
            'ROL' => 'Roll',
            'SKD' => 'Skid',
            'SPL' => 'Spool',
            'TBE' => 'Tube',
            'TNK' => 'Tank',
            'UNT' => 'Unit',
            'VPK' => 'Van Pack',
            'WRP' => 'Wrapped',
        ];

        foreach ($packingTypes as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function getFreightClassOptions(): array
    {
        $options = [];

        $freightClasses = [
            '50' => '50',
            '55' => '55',
            '60' => '60',
            '65' => '65',
            '70' => '70',
            '77.5' => '77.5',
            '85' => '85',
            '92.5' => '92.5',
            '100' => '100',
            '110' => '110',
            '125' => '125',
            '150' => '150',
            '175' => '175',
            '200' => '200',
            '250' => '250',
            '300' => '300',
            '400' => '400',
            '500' => '500',
        ];

        foreach ($freightClasses as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }
}
