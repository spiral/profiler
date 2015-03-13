<?
use Spiral\Core\Core;
use Spiral\Core\Loader;
use Spiral\Facades\View;
use Spiral\Facades\File;

?>
<div class="plugin" id="profiler-plugin-environment">
    <div class="title top-title">Spiral Environment, PHP (<?= phpversion() ?>)</div>
    <div style="width: 40%; float: left; display: inline-block;">
        <table>
            <tbody>
            <tr>
                <th colspan="3">Active HTTP Routes</th>
            </tr>
            <?
            if (false)
            {
            }
            ?>
            </tbody>
        </table>
        <br/>
        <?php
        if (!\Spiral\Components\View\View::getInstance()->getConfig()['caching']['enabled'])
        {
            ?>
            <div class="error">
                View cache is disabled, this will slow down your application a lot. Do not forget to
                turn view cache on
                later.<br/>
                Cache flag located in <b>application/config/view.php</b> configuration
                file.
            </div>
        <?
        }
        ?>
        <table>
            <tbody>
            <tr>
                <th colspan="3">View Namespaces</th>
            </tr>
            <?
            foreach (View::getNamespaces() as $name => $directories)
            {
                foreach ($directories as $directory)
                {
                    ?>
                    <tr>
                        <td>
                            <b><?= $name ?></b>
                        </td>
                        <td>
                            <?= File::normalizePath($directory) ?>
                        </td>
                    </tr>
                    <?php
                    $name = '';
                }
            }
            ?>
            </tbody>
        </table>
        <br/>
        <table>
            <tbody>
            <tr>
                <th colspan="3">Components</th>
            </tr>
            <?
            //TODO: Change spl_object_hash to something else
            foreach (Core::getBindings() as $component => $resolver)
            {
                ?>
                <tr>
                    <td><?= $component ?></td>
                    <td><?php
                        if (is_string($resolver))
                        {
                            echo e($resolver);
                        }
                        elseif (is_array($resolver))
                        {
                            echo '<b>late resolve</b> ' . ($resolver[1] ? '(singleton)' : '');
                        }
                        elseif (is_object($resolver))
                        {
                            echo '<b class="text-blue">' . get_class($resolver) . '</b><br/>';
                        }
                        ?>
                    </td>
                    <td>
                        <?
                        if (is_object($resolver))
                        {
                            echo strtoupper(substr(spl_object_hash($resolver), 13, 3));
                        }
                        else
                        {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?
            }
            ?>
            </tbody>
        </table>
        <br/>
        <table>
            <tbody>
            <tr>
                <th colspan="2">Server Options</th>
            </tr>
            <?

            $serverVariables = array(
                'IP ADDRESS'    => 'SERVER_ADDR',
                'SOFTWARE'      => 'SERVER_SOFTWARE',
                'DOCUMENT ROOT' => 'DOCUMENT_ROOT',
                'PROTOCOL'      => 'SERVER_PROTOCOL'
            );

            $phpVariables = array(
                'PHP EXPOSING'           => '(bool)expose_php',
                'EXTENSIONS DIRECTORY'   => 'extension_dir',
                'FILE UPLOADS'           => '(bool)file_uploads',
                'FILE UPLOADS DIRECTORY' => 'upload_tmp_dir',
                'POST DATA SIZE LIMIT'   => 'post_max_size',
                'MAX FILESIZE'           => 'upload_max_filesize',
                'MEMORY LIMIT'           => 'memory_limit',
                'TIME LIMIT'             => 'max_execution_time'
            );

            foreach ($serverVariables as $title => $variable)
            {
                if (array_key_exists($variable, $_SERVER))
                {
                    ?>
                    <tr>
                        <td align="right"><?= str_replace(' ', '&nbsp;', $title) ?></td>
                        <td><?= $_SERVER[$variable] ?></td>
                    </tr>
                <?
                }
            }

            $phpEnvironment = ini_get_all();
            foreach ($phpVariables as $title => $variable)
            {
                $variableName = preg_replace('/\(.+?\)/', '', $variable);
                if (array_key_exists($variableName, $phpEnvironment))
                {
                    if (!strncasecmp($variable, '(bool)', 6))
                    {
                        $value = $phpEnvironment[$variableName]['local_value'] ? '<b>TRUE</b>' : '<b>FALSE</b>';
                    }
                    else
                    {
                        $value = $phpEnvironment[$variableName]['local_value'];
                    }
                    ?>
                    <tr>
                        <td align="right"><?= str_replace(' ', '&nbsp;', $title) ?></td>
                        <td><?= $value ?></td>
                    </tr>
                <?
                }
            }
            ?>
            </tbody>
        </table>
        <br/>
        <table>
            <tbody>
            <tr>
                <th colspan="3">Available extensions</th>
            </tr>
            <?
            $extensions = get_loaded_extensions();
            while ($extension = next($extensions))
            {
                ?>
                <tr>
                    <td><?= $extension ?></td>
                    <td><?= next($extensions) ?></td>
                    <td><?= next($extensions) ?></td>
                </tr>
            <?
            }
            ?>
            </tbody>
        </table>
    </div>
    <div style="width: 60%; float: left; display: inline-block;">
        <div style="padding: 10px; padding-top: 0;">
            <table>
                <tbody>
                <tr>
                    <th colspan="2">Classes</th>
                </tr>
                <?php

                $application = File::normalizePath(directory('application'));
                $libraries = File::normalizePath(directory('libraries'));
                $framework = File::normalizePath(directory('framework'));

                foreach (Loader::getInstance()->getClasses() as $class => $filename)
                {
                    $filename = File::normalizePath($filename);

                    $color = false;

                    if (strpos($filename, $application) === 0)
                    {
                        $color = 'blue';
                    }

                    if (strpos($filename, $libraries) === 0 && strpos($filename, $framework) === false)
                    {
                        $color = 'yellow';
                    }
                    ?>
                    <tr class="<?= $color ? $color . '-td' : '' ?>">
                        <td><?= $class ?></td>
                        <td><?= File::relativePath($filename) ?></td>
                    </tr>
                <?
                }
                ?>
                </tbody>
            </table>

            <br/>
            <table>
                <tbody>
                <tr>
                    <th colspan="2">Included files</th>
                </tr>
                <?

                $total = 0;
                foreach (get_included_files() as $filename)
                {
                    if (!file_exists($filename))
                    {
                        continue;
                    }

                    $filesize = filesize($filename);
                    $total += $filesize;
                    ?>
                    <tr>
                        <td><?= File::normalizePath($filename) ?></td>
                        <td align="right"><?= StringHelper::formatBytes($filesize) ?></td>
                    </tr>
                <?
                }
                ?>
                <tr>
                    <td align="right">TOTAL:</td>
                    <td align="right"><?= StringHelper::formatBytes($total) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>