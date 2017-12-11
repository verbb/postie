<?php
namespace Craft;

class Postie_PluginController extends BaseController
{
    // Public Methods
    // =========================================================================

    public function actionCheckRequirements()
    {
        $dependencies = craft()->postie_plugin->checkRequirements();

        if ($dependencies) {
            $this->renderTemplate('postie/dependencies', [
                'dependencies' => $dependencies,
            ]);
        }
    }
}