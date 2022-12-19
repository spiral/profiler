<?php

declare(strict_types=1);

namespace Spiral\Profiler;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use SpiralPackages\Profiler\Driver\DriverInterface;
use SpiralPackages\Profiler\DriverFactory;
use SpiralPackages\Profiler\Storage\StorageInterface;
use SpiralPackages\Profiler\Storage\WebStorage;
use Symfony\Component\HttpClient\NativeHttpClient;

final class ProfilerBootloader extends Bootloader
{
    protected const BINDINGS = [
        StorageInterface::class => [self::class, 'createStorage'],
        DriverInterface::class => [self::class, 'createDriver'],
    ];

    private function createStorage(EnvironmentInterface $env): StorageInterface
    {
        return new WebStorage(
            new NativeHttpClient(),
            $env->get('PROFILER_ENDPOINT', 'http://127.0.0.1/api/profiler/store'),
        );
    }

    private function createDriver(): DriverInterface
    {
        return DriverFactory::detect();
    }
}