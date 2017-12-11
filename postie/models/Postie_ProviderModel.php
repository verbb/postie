<?php

namespace Craft;

/**
 * Class Postie_ProviderModel
 *
 * @property integer id
 * @property string  handle
 * @property string  name
 * @property array   settings
 * @property string  markUpRate
 * @property string  markUpBase
 * @property boolean enabled
 */
class Postie_ProviderModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    /**
     * Get Handle
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Check if enabled set true
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get API settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Get mark-up rate
     *
     * @return string
     */
    public function getMarkUpRate()
    {
        return $this->markUpRate;
    }

    /**
     * Get mark-up base. Ether PERCENTAGE or VALUE
     *
     * @return string
     */
    public function markUpBase()
    {
        return $this->markUpBase;
    }

    /**
     * Define Attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return [
            'id'         => AttributeType::Number,
            'handle'     => [AttributeType::String, 'required' => true],
            'name'       => [AttributeType::String, 'required' => true],
            'enabled'    => [AttributeType::Bool, 'required' => false],
            'settings'   => [AttributeType::String, 'required' => false],
            'markUpRate' => [AttributeType::String],
            'markUpBase' => [
                AttributeType::Enum,
                'required' => true,
                'values'   => Postie_ProviderRecord::getMarkUpBaseOptions(),
                'default'  => Postie_ProviderRecord::PERCENTAGE,
            ],
        ];
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['enabled', 'checkApiSetting'];

        return $rules;
    }

    public function checkApiSetting($attribute)
    {
        if ($this->$attribute == 1) {

            $error = false;

            if (count ($this->settings) > 0) {
                foreach ($this->settings as $key => $value) {

                    if (!$value || empty($value)) {
                        $error = true;

                        $settingsLabel = preg_replace('/\bApi\b/', 'API', ucwords(preg_replace('/([A-Z])/', ' $1', $key)));
                        $message = Craft::t('{attribute} is required.', ['attribute' => $settingsLabel]);
                        $this->addError($key, $message);
                    }
                }
            }

            if ($error) {
                $this->addError($attribute, Craft::t('To activate this provider please fill in all required fields.'));
            }
        }
    }
}