<?php

declare(strict_types=1);

namespace Spiral\Profiler\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Boot\Environment;
use Spiral\Profiler\ProfilerBootloader;
use SpiralPackages\Profiler\Driver\NullDriver;
use SpiralPackages\Profiler\Exception\ProfilerNotFoundException;
use SpiralPackages\Profiler\Storage\NullStorage;
use SpiralPackages\Profiler\Storage\WebStorage;

/**
 * @coversDefaultClass \Spiral\Profiler\ProfilerBootloader
 */
final class ProfilerBootloaderTest extends TestCase
{
    /**
     * @covers ::createStorage
     * @dataProvider dataProfilerEnableCreateStorage
     */
    public function testProfilerEnableCreateStorage(mixed $value, string $class): void
    {
        $env = new Environment(['PROFILER_ENABLE' => $value]);
        $bootloader = new ProfilerBootloader();
        $storage = (fn() => $this->createStorage($env))->call($bootloader);
        self::assertInstanceOf($class, $storage);
    }

    public function dataProfilerEnableCreateStorage(): iterable
    {
        // disable
        yield [false, NullStorage::class];
        yield [0, NullStorage::class];
        yield ['', NullStorage::class];
        yield ['false', NullStorage::class];
        // enable
        yield [null, WebStorage::class];
        yield [true, WebStorage::class];
        yield [1, WebStorage::class];
        yield ['true', WebStorage::class];
    }

    /**
     * @covers ::createDriver
     * @dataProvider dataProfilerEnableCreateDriver
     */
    public function testProfilerEnableCreateDriver(mixed $value, string $class): void
    {
        $env = new Environment(['PROFILER_ENABLE' => $value]);
        $bootloader = new ProfilerBootloader();
        $storage = (fn() => $this->createDriver($env))->call($bootloader);
        self::assertInstanceOf($class, $storage);
    }

    public function dataProfilerEnableCreateDriver(): iterable
    {
        // disable
        yield [false, NullDriver::class];
        yield [0, NullDriver::class];
        yield ['', NullDriver::class];
        yield ['false', NullDriver::class];
    }

    /**
     * @covers ::createDriver
     * @dataProvider dataProfilerEnableCreateDriverThrow
     */
    public function testProfilerEnableCreateDriverThrow(mixed $value): void
    {
        if (
            \function_exists('xhprof_enable') ||
            \function_exists('tideways_xhprof_enable') ||
            \function_exists('uprofiler_enable')
        ) {
            self::markTestSkipped('You should disable all profile extensions.');
        }

        self::expectException(ProfilerNotFoundException::class);
        $env = new Environment(['PROFILER_ENABLE' => $value]);
        $bootloader = new ProfilerBootloader();
        (fn() => $this->createDriver($env))->call($bootloader);
    }

    public function dataProfilerEnableCreateDriverThrow(): iterable
    {
        // enable
        yield [true];
        yield [1];
        yield ['true'];
    }
}
