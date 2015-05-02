<?php
use Spiral\Components\Debug\Logger;

SqlFormatter::$pre_attributes = '';
?>
<div class="plugin" id="profiler-plugin-logging">
    <div class="title top-title">Log Messages</div>
    <table id="debug-messages-table">
        <thead>
        <tr>
            <th>
                <select id="debug-messages"></select>
            </th>
            <th>Level</th>
            <th>Message</th>
        </tr>
        </thead>
        <tbody>
        <?
        $colors = array(
            'warning'  => 'yellow',
            'notice'   => 'yellow',
            'critical' => 'red',
            'alert'    => 'red',
            'error'    => 'red'
        );

        foreach (Logger::logMessages() as $message)
        {
            $class = '';
            if (isset($colors[$message[2]]))
            {
                $class = $colors[$message[2]] . '-td';
            }

            //SQL queries from drivers
            if (isset($message[4]['query']) && strpos($message[0], 'Spiral\Components\DBAL\Drivers') === 0)
            {
                $message[3] = SqlFormatter::highlight($message[3]);

                //Removing PRE
                $message[3] = trim(substr($message[3], 6, -6));
            }

            ?>
            <tr class="caller-<?= $message[0] ?> <?= $class ?>">
                <td><b><?= $message[0] ?></b></td>
                <td><?= strtoupper($message[2]) ?></td>
                <td style="unicode-bidi: embed; white-space: pre;"
                    width="100%"><?= $message[3] ?></td>
            </tr>
        <?
        }

        if (!Logger::logMessages())
        {
            ?>
            <tr>
                <td colspan="3" align="center" style="padding: 20px;">
                    No logs recorded. :(
                </td>
            </tr>
        <?
        }

        ?>
        </tbody>
    </table>
</div>