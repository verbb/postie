<?php

?>
<h1>Postie Log</h1>

<table class="table table-striped table-bordered">
    <colgroup>
        <col>
        <col>
        <col width="80%">
    </colgroup>
    <thead>
    <tr>
        <th>Time</th>
        <th>Level</th>
        <th>Message</th>
    </tr>
</thead>
<tbody>

<?php
if (!$panel->data['logs']) {
    echo 'No log data.';
}

foreach ($panel->data['logs'] as $i => $log) {
    $level = strtolower($log['level_name']);

    if ($level === 'error') {
        $class = 'table-danger';
    } else {
        $class = '';
    }
?>
    <tr class="<?php echo $class; ?>" data-key="<?php echo $i; ?>">
        <td class="word-break-keep"><?php echo $log['datetime']->format('Y-m-d H:i:s'); ?></td>
        <td><?php echo $level; ?></td>
        <td><?php echo $log['message']; ?></td>
    </tr>
<?php } ?>

</tbody>
</table>