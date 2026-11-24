<?php
namespace verbb\postie\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

/**
 * Plugin Kit's setup checklist and state panel, for the pages that show them.
 */
class PluginKitAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/postie/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
        ];

        $this->css = [
            'plugin-kit.css',
        ];

        $this->js = [
            'plugin-kit.js',
        ];

        parent::init();
    }
}
