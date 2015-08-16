<?php
/**
 * Environment definition requires some components.
 *
 * @var \Spiral\Profiler\Profiler      $profiler
 * @var \Spiral\Core\Container         $container Required to get all existed bindings.
 * @var \Spiral\Core\Components\Loader $loader    List of loaded classes.
 * @var \Spiral\Http\HttpDispatcher    $http      Routes.
 * @var \Spiral\Views\ViewManager      $views     View namespaces and caching state.
 * @var \Spiral\Core\Container         $container Required to get all existed bindings.
 * @var \Spiral\Files\FileManager      $files     Files operations and etc.
 */
$container = $profiler->getContainer();
$loader = $profiler->getContainer()->get(\Spiral\Core\Components\Loader::class);
$http = $profiler->getContainer()->get(\Spiral\Http\HttpDispatcher::class);
$views = $profiler->getContainer()->get(\Spiral\Views\ViewManager::class);
$files = $profiler->getContainer()->get(\Spiral\Files\FileManager::class);
?>
<div class="plugin" id="profiler-plugin-environment">
    <div class="title top-title">
        [[Spiral Environment]], PHP (<?= phpversion() ?>), Spiral <?= \Spiral\Core\Core::VERSION ?>
    </div>
    <div class="narrow-col">
        <?php
        if (!$views->config()['cache']['enabled']) {
            ?>
            <div class="error">
                [[View cache is disabled, this will slow down your application a lot.]]
                [[Do not forget to turn view cache on later.]]<br/>
                [[Cache flag located in <b>application/config/views.php</b> configuration file.]]
            </div>
            <?php
        }
        ?>
        <table>
            <tbody>
            <tr>
                <th colspan="3">[[Active HTTP Routes]]</th>
            </tr>
            <?php
            foreach ($http->router()->getRoutes() as $route) {
                ?>
                <tr>
                    <td class="nowrap">
                        <b><?= $route->getName() ?></b>
                    </td>
                    <td>
                        <?php
                        if ($route instanceof \Spiral\Http\Routing\AbstractRoute) {
                            echo e($route->getPattern());
                        } else {
                            echo '&ndash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?= get_class($route); ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="3">[[View Namespaces]]</th>
            </tr>
            <?php
            foreach ($views->getNamespaces() as $namespace => $directories) {
                foreach ($directories as $directory) {
                    ?>
                    <tr>
                        <td class="nowrap">
                            <b><?= $namespace ?></b>
                        </td>
                        <td>
                            <?= $files->normalizePath($directory) . '/' ?>
                        </td>
                    </tr>
                    <?php
                    $namespace = '';
                }
            }
            ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="3">[[Components]]</th>
            </tr>
            <?php
            $classIDs = [];
            foreach ($container->getBindings() as $alias => $resolver) {
                ?>
                <tr>
                    <td class="nowrap"><?= $alias ?></td>
                    <td>
                        <?php
                        if (empty($resolver)) {
                            echo 'none';
                        } elseif (is_string($resolver)) {
                            echo e($resolver);
                        } elseif (is_array($resolver)) {
                            echo '<b>late resolve</b> ' . ($resolver[1] ? '(singleton)' : '');
                        } elseif (is_object($resolver)) {
                            echo '<b class="text-blue">' . get_class($resolver) . '</b><br/>';
                        }
                        ?>
                    </td>
                    <td class="nowrap">
                        <?php
                        if (is_object($resolver)) {
                            //Resolving unique object id
                            if (!isset($classIDs[spl_object_hash($resolver)])) {
                                $classIDs[spl_object_hash($resolver)] = count($classIDs) + 16;
                            }

                            $classID = $classIDs[spl_object_hash($resolver)];
                            echo strtoupper(dechex($classID));
                        } else {
                            echo '&ndash;';
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="2">[[Server Options]]</th>
            </tr>
            <?php
            $serverVariables = [
                '[[IP ADDRESS]]'    => 'SERVER_ADDR',
                '[[SOFTWARE]]'      => 'SERVER_SOFTWARE',
                '[[DOCUMENT ROOT]]' => 'DOCUMENT_ROOT',
                '[[PROTOCOL]]'      => 'SERVER_PROTOCOL'
            ];

            $phpVariables = [
                '[[PHP EXPOSING]]'           => '(bool)expose_php',
                '[[EXTENSIONS DIRECTORY]]'   => 'extension_dir',
                '[[FILE UPLOADS]]'           => '(bool)file_uploads',
                '[[FILE UPLOADS DIRECTORY]]' => 'upload_tmp_dir',
                '[[POST DATA SIZE LIMIT]]'   => 'post_max_size',
                '[[MAX FILESIZE]]'           => 'upload_max_filesize',
                '[[MEMORY LIMIT]]'           => 'memory_limit',
                '[[TIME LIMIT]]'             => 'max_execution_time'
            ];

            foreach ($serverVariables as $title => $variable) {
                if (array_key_exists($variable, $_SERVER)) {
                    ?>
                    <tr>
                        <td align="right" class="nowrap"><?= str_replace(' ', '&nbsp;',
                                $title) ?></td>
                        <td><?= $_SERVER[$variable] ?></td>
                    </tr>
                    <?php
                }
            }

            $phpEnvironment = ini_get_all();
            foreach ($phpVariables as $title => $variable) {
                $variableName = preg_replace('/\(.+?\)/', '', $variable);
                if (array_key_exists($variableName, $phpEnvironment)) {
                    if (!strncasecmp($variable, '(bool)', 6)) {
                        $value = $phpEnvironment[$variableName]['local_value']
                            ? '<b>[[TRUE]]</b>'
                            : '<b>[[FALSE]]</b>';
                    } else {
                        $value = $phpEnvironment[$variableName]['local_value'];
                    }
                    ?>
                    <tr>
                        <td align="right"><?= str_replace(' ', '&nbsp;', $title) ?></td>
                        <td><?= $value ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="3">[[Available extensions]]</th>
            </tr>
            <?php
            $extensions = get_loaded_extensions();
            while ($extension = next($extensions)) {
                ?>
                <tr>
                    <td><?= $extension ?></td>
                    <td><?= next($extensions) ?></td>
                    <td><?= next($extensions) ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="wide-col">
        <table>
            <tbody>
            <tr>
                <th colspan="2">[[Loaded Classes]]</th>
            </tr>
            <?php

            $application = $files->normalizePath(directory('application'));
            $libraries = $files->normalizePath(directory('libraries'));
            $framework = $files->normalizePath(directory('framework'));

            foreach ($loader->getClasses() as $class => $filename) {
                $filename = $files->normalizePath($filename);

                $color = '';
                if (strpos($filename, $application) === 0) {
                    $color = 'blue';
                }

                if (
                    strpos($filename, $libraries) === 0
                    && strpos($filename, $framework) === false
                ) {
                    $color = 'yellow';
                }
                ?>
                <tr class="<?= $color ? $color . '-td' : '' ?>">
                    <td><?= $class ?></td>
                    <td><?= $files->relativePath($filename, directory('root')) ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="2">[[Included files]]</th>
            </tr>
            <?php
            $totalSize = 0;
            foreach (get_included_files() as $filename) {
                if (!file_exists($filename)) {
                    continue;
                }

                $filesize = filesize($filename);
                $totalSize += $filesize;
                ?>
                <tr>
                    <td><?= $files->normalizePath($filename) ?></td>
                    <td align="right"
                        class="nowrap"><?= \Spiral\Support\StringHelper::bytes($filesize) ?></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td align="right">TOTAL:</td>
                <td align="right"
                    class="nowrap"><?= \Spiral\Support\StringHelper::bytes($totalSize) ?></td>
            </tr>
            </tbody>
        </table>

    </div>
</div>