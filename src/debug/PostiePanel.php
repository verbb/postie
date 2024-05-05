<?php
namespace verbb\postie\debug;

use Craft;

use yii\debug\Panel;

use Monolog\Handler\TestHandler;

class PostiePanel extends Panel
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Postie';
    }

    public function getSummary(): string
    {
        return Craft::$app->getView()->render('@verbb/postie/views/debug/summary', [
            'panel' => $this,
        ]);
    }

    public function getDetail(): string
    {
        return Craft::$app->getView()->render('@verbb/postie/views/debug/detail', [
            'panel' => $this,
        ]);
    }

    public function save()
    {
        $logs = [];

        if ($logTarget = (Craft::$app->getLog()->targets['verbb\postie\*'] ?? null)) {
            if ($logger = $logTarget->getLogger()) {
                foreach ($logger->getHandlers() as $handler) {
                    if ($handler instanceof TestHandler) {
                        $logs = $handler->getRecords();
                    }
                }
            }
        }

        return ['logs' => $logs];
    }
}
