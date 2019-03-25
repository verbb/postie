<?php
namespace verbb\postie\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PostieAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/postie/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/postie.css',
        ];

        $this->js = [
            'js/postie.js',
        ];

        parent::init();
    }
}
