<?php
/**
 * Environment definition requires some components.
 *
 * @var \Spiral\Core\Container            $container
 * @var \Spiral\Core\Loader               $loader
 * @var \Spiral\Http\HttpDispatcher       $http
 * @var \Spiral\Views\Configs\ViewsConfig $viewsConfig
 * @var \Spiral\Files\FileManager         $files
 */
$loader = $container->get(\Spiral\Core\Loader::class);
$http = $container->get(\Spiral\Http\HttpDispatcher::class);
$files = $container->get(\Spiral\Files\FileManager::class);
$viewsConfig = $container->get(\Spiral\Views\Configs\ViewsConfig::class);
?>
<div class="plugin" id="profiler-plugin-environment">
    <div class="title top-title">
        [[Spiral Environment]], PHP (<?= phpversion() ?>), Spiral <?= \Spiral\Core\Core::VERSION ?>
    </div>
    <div class="narrow-col">
        <?php if (!$viewsConfig->cacheEnabled()) { ?>
            <div class="error">
                [[View cache is disabled, view files recompied on every request.]]
            </div>
        <?php } ?>
        <table>
            <tbody>
            <tr>
                <th colspan="3">[[View Namespaces]]</th>
            </tr>
            <?php
            foreach ($viewsConfig->getNamespaces() as $namespace => $directories) {
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
                <th colspan="3">[[Container Bindings]]</th>
            </tr>
            <?php
            $classIDs = [];
            foreach ($container->getBindings() as $alias => $resolver) {
                if (lcfirst($alias) == $alias) {
                    //Potential short binding
                    $alias = "<b>{$alias}</b>";
                }
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
                            echo '<b>lazy resolver</b> ' . ($resolver[1] ? '(singleton)' : '');
                        } elseif (is_object($resolver)) {
                            echo '<b class="text-blue">' . get_class($resolver) . '</b><br/>';
                        }
                        ?>
                    </td>
                    <td class="nowrap">
                        <?php
                        if (!empty($resolver) && is_object($resolver)) {
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
            <?php } ?>
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
                        <td align="right" class="nowrap">
                            <?= str_replace(' ', '&nbsp;', $title) ?>
                        </td>
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

            foreach ($loader->getClasses() as $class => $filename) {
                $filename = $files->normalizePath($filename);

                $color = '';
                if (strpos($filename, $application) === 0) {
                    $color = 'blue';
                }

                if (
                    strpos($filename, $libraries) === 0
                    && substr($class, 0, 7) != 'Spiral\\'
                ) {
                    $color = 'yellow';
                }

                if (
                    strpos($class, 'Exception') !== false
                ) {
                    $color = 'red';
                }
                ?>
                <tr class="<?= $color ? $color . '-td' : '' ?>">
                    <td><?= $class ?></td>
                    <td><?= $files->relativePath($filename, directory('root')) ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <th colspan="2">[[Included files]]</th>
            </tr>
            <?php
            $totalCount = 0;
            $totalSize = 0;
            foreach (get_included_files() as $filename) {
                if (!file_exists($filename)) {
                    continue;
                }

                $filesize = filesize($filename);
                $totalCount++;
                $totalSize += $filesize;
                ?>
                <tr>
                    <td><?= $files->normalizePath($filename) ?></td>
                    <td align="right" class="nowrap">
                        <?= \Spiral\Support\Strings::bytes($filesize) ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td align="right">TOTAL:</td>
                <td align="right" class="nowrap">
                    <?= \Spiral\Support\Strings::bytes($totalSize) . ", " . number_format($totalCount) . " file(s)" ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>