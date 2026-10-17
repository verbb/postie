<?php

$vendor = getenv('VERBB_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
require $vendor . '/autoload.php';
require_once $vendor . '/yiisoft/yii2/Yii.php';
require_once $vendor . '/craftcms/cms/src/Craft.php';
$checks = 0;
$check = static function(bool $value, string $message) use (&$checks): void {
    if (!$value) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$key = 'postie-craft4';
$order = (new ReflectionClass(\craft\commerce\elements\Order::class))->newInstanceWithoutConstructor();
foreach ([(object)['status' => 'ok'], ['status' => 'ok']] as $response) {
    $event = new \verbb\postie\events\ModifyRatesEvent(['rates' => ['ground' => ['amount' => '12.50']], 'response' => $response, 'order' => $order]);
    $event->rates['ground']['amount'] = '9.50';
    $check($event->rates['ground']['amount'] === '9.50' && $event->response === $response && $event->order === $order, 'Legacy UPS event data must support mutable rates and object/array responses.');
}

echo "Passed $checks behavioral regression checks on PHP " . PHP_VERSION . ".\n";
