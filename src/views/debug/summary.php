<?php
$info = 0;
$errors = 0;

foreach ($panel->data['logs'] as $log) {
    $level = strtolower($log['level_name']);

    if ($level === 'error') {
        $errors++;
    } else {
        $info++;
    }
}
?>

<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        Postie

        <?php if ($info) {
            echo '<span class="yii-debug-toolbar__label">' . $info . '</span>';
        } ?>

        <?php if ($errors) {
            echo '<span class="yii-debug-toolbar__label yii-debug-toolbar__label_error">' . $errors . '</span>';
        } ?>
    </a>
</div>
