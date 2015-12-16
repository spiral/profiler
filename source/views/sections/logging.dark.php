<?php
/**
 * @var \Spiral\Profiler\ProfilerWrapper $profiler
 */
?>
<div class="plugin" id="profiler-plugin-logging">
    <div class="title top-title">[[Log Messages]]</div>
    <table id="debug-messages-table">
        <thead>
        <tr>
            <th>
                <select id="debug-messages"></select>
            </th>
            <th>[[Level]]</th>
            <th>[[Message]]</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $colors = [
            'warning'  => 'yellow',
            'notice'   => 'yellow',
            'critical' => 'red',
            'alert'    => 'red',
            'error'    => 'red'
        ];

        foreach ($profiler->logMessages() as $message) {
            $channel = $message['channel'];
            $level = strtolower($message['level_name']);

            $class = '';
            if (isset($colors[$level])) {
                $class = $colors[$level] . '-td';
            }

            ?>
            <tr class="caller-<?= $channel ?> <?= $class ?>">
                <td class="nowrap"><b><?= $channel ?></b></td>
                <td class="nowrap"><?= strtoupper($level) ?></td>
                <td style="unicode-bidi: embed; white-space: pre;" width="100%"><?php
                    echo $profiler->formatMessage(
                        $message['channel'],
                        $message['message'],
                        $message['context']
                    );
                    ?>
                </td>
            </tr>
            <?php
        }
        if (empty($profiler->logMessages())) {
            ?>
            <tr>
                <td colspan="3" align="center" style="padding: 20px;">
                    [[No log messages were created while performing this user request.]]
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>