<?php
/**
 * @var \Psr\Http\Message\ResponseInterface $response
 * @var float                               $elapsed
 */
$elapsed = max($elapsed, 0.001);
?>
<dark:use path="profiler:sections/*" namespace="plugin"/>
<!-- Profiler panel beginning. -->
<div id="spiral-profiler">
    <link rel="stylesheet" type="text/css" href="@{basePath}resources/styles/profiler/profiler.css"/>
    <script type="text/javascript" src="@{basePath}resources/scripts/profiler/profiler.js"></script>

    <div id="dbg-prf" class="profiler">
        <div id="dbg-prf-shadow" class="shadow"></div>
        <a class="spiral-link" id="dbg-prf-link"></a>

        <div id="dbg-prf-options" class="options">
            <div id="dbg-prf-mode-status" class="option mode" style="display: none">
                <a href="#" id="js-mode-switch"></a>
            </div>
            <div id="dbg-prf-option-status" class="option status">
                <?= $response->getStatusCode() . ' ' . $response->getReasonPhrase() ?>
            </div>
            <div id="dbg-prf-option-elapsed" class="option elapsed">
                <?= number_format(1000 * $elapsed) ?> [[ms]]
            </div>

            <div id="dbg-prf-option-memory" class="option memory">
                <span title="[[Peak Usage]]"><?= \Spiral\Support\Strings::bytes(memory_get_peak_usage(true)) ?></span>
                /
                <span title="[[Current Usage]]"><?= \Spiral\Support\Strings::bytes(memory_get_usage(true)) ?></span>
            </div>

            <!-- Plugins. -->
            <div id="dbg-profiler-plugin-environment" class="option environment" plugin="environment">
                <a title="[[Environment]]"></a>
            </div>

            <div id="dbg-profiler-plugin-variables" class="option variables" plugin="variables">
                <a title="[[Application Variables]]"></a>
            </div>

            <div id="dbg-profiler-plugin-benchmarks" class="option benchmarks" plugin="benchmarks">
                <a title="[[Application Profiling]]"></a>
            </div>

            <div id="dbg-profiler-plugin-logging" class="option logging" plugin="logging">
                <a title="[[Log Messages]]"></a>
            </div>
            <!-- End of Plugins. -->

            <div id="dbg-prf-options-option-close" class="option close option-close"></div>
        </div>

        <div id="dbg-prf-content" class="content">
            <div id="dbg-prf-content-option-close" class="option close option-close"></div>
            <div class="inner-modal">
                <plugin:environment/>
                <plugin:variables/>
                <plugin:benchmarks/>
                <plugin:logging/>
            </div>
        </div>
        <div id="dbg-js-content" class="content">
            <div id="dbg-js-content-option-close" class="option close option-close"></div>
        </div>
    </div>
</div>