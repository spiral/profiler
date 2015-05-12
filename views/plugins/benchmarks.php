<div class="plugin" id="profiler-plugin-benchmarks">
    <div class="title top-title">Application Profiling</div>

    <div class="flow" id="profiler-time-flow">
        <table>
            <thead>
            <tr>
                <th style="width: 10%">Record</th>
                <th>Timeline</th>
            </tr>
            </thead>
        </table>
        <?php
        $colors = array(
            2    => 'default',
            30   => 'orange',
            1000 => 'red'
        );

        $totalTime = microtime(true) - SPIRAL_INITIAL_TIME;
        foreach (\Spiral\Components\Debug\Debugger::getBenchmarks() as $record => $data)
        {
            if (!isset($data[2]))
            {
                continue;
            }

            $lineLength = 100 * ($data[2] - $data[0]) / PROFILER_TIME_ELAPSED;
            foreach ($colors as $length => $color)
            {
                if ($lineLength < $length)
                {
                    break;
                }
            }

            /**
             * @var float $started
             */
            $linePadding = 100 * ($data[0] - $started) / PROFILER_TIME_ELAPSED;

            if ($linePadding > 100)
            {
                continue;
            }

            $context = '';
            if (strpos($record, '|') !== false)
            {
                list($record, $context) = explode('|', $record);
            }
            ?>
            <div class="timeline clearfix"
                 onclick="this.setAttribute('status', this.getAttribute('status') == 'open' ? 'closed' : 'open')"
                 status="closed">
                <div class="clearfix">
                    <div class="name"><?= $record ?></div>
                    <div class="time <?= !empty($color) ? 'time-' . $color : '' ?>">
                        <div
                            style="margin-left: <?= $linePadding ?>%; width: <?= $lineLength ?>%;"></div>
                    </div>
                </div>
                <div class="details clearfix">
                    <strong style="float: right;">
                        <?= !empty($context) ? 'Context: ' . $context : '' ?>
                    </strong>
                    <strong>Record: <?= $record ?></strong>
                    <br>

                    <p style="float: right;">
                        Memory: <?= \spiral\helpers\StringHelper::formatBytes($data[3] - $data[1]) ?>
                    </p>

                    <p>
                        Elapsed: ~<?= number_format(($data[2] - $data[0]) * 1000) ?> ms
                    </p>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>