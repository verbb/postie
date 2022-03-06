<?php
namespace verbb\postie\variables;

use verbb\postie\Postie;

class PostieVariable
{
    // Public Methods
    // =========================================================================

    public function getPluginName(): string
    {
        return Postie::$plugin->getPluginName();
    }
}