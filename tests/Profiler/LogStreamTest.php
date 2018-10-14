<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Profiler\Tests;

use Codedungeon\PHPCliColors\Color;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Logger\Event\LogEvent;
use Spiral\Logger\LogsInterface;
use Spiral\Profiler\Bootloader\LogStreamBootloader;
use Spiral\Profiler\LogStream;

class LogStreamTest extends TestCase
{
    public function testBootloader()
    {
        $b = new LogStreamBootloader();
        $logs = $this->createMock(LogsInterface::class);
        $logs->expects(new InvokedCount(0))->method('addListener');

        $env = $this->createMock(EnvironmentInterface::class);
        $env->method('get')->willReturn(null);

        $b->boot($env, $logs, new LogStream());
    }

    public function testBootloaderActivate()
    {
        $b = new LogStreamBootloader();
        $logs = $this->createMock(LogsInterface::class);
        $logs->expects(new InvokedCount(1))->method('addListener');

        $env = $this->createMock(EnvironmentInterface::class);
        $env->method('get')->willReturn(true);

        $b->boot($env, $logs, new LogStream());
    }

    public function testStreamNoColors()
    {
        $out = fopen('php://memory', 'rb+');

        $stream = new LogStream($out, false);
        $stream->__invoke(new LogEvent(
            new \DateTime(),
            'debug',
            'debug',
            "debug message\nnew line\n",
            []
        ));

        fseek($out, 0);
        $written = fread($out, 1000);
        fclose($out);

        $this->assertNotEmpty($written);
        $this->assertContains("debug", $written);
    }

    public function testStreamColors()
    {
        $out = fopen('php://memory', 'rb+');

        $stream = new LogStream($out, true);
        $stream->__invoke(new LogEvent(
            new \DateTime(),
            'debug',
            'debug',
            "debug message\nnew line\n",
            []
        ));

        fseek($out, 0);
        $written = fread($out, 1000);
        fclose($out);

        $this->assertNotEmpty($written);
        $this->assertContains("debug", $written);
        $this->assertContains(Color::GRAY, $written);
    }
}