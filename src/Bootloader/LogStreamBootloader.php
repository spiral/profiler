<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Profiler\Bootloader;

use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\Bootloader\Bootloader;
use Spiral\Logger\LogsInterface;
use Spiral\Profiler\LogStream;

/**
 * Displays all logs in RR debug console in real-time. Make sure to add after LogsInterface being
 * declared.
 */
class LogStreamBootloader extends Bootloader
{
    const BOOT = true;

    /**
     * @param EnvironmentInterface $environment
     * @param LogsInterface        $logs
     * @param LogStream            $server
     */
    public function boot(EnvironmentInterface $environment, LogsInterface $logs, LogStream $server)
    {
        if ($environment->get('RR') === null) {
            return;
        }

        $logs->addListener($server);
    }
}