<?php

declare(strict_types=1);

namespace Spiral\Profiler\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\CoreInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Profiler\ProfilerInterceptor;

/**
 * @coversDefaultClass \Spiral\Profiler\ProfilerMiddleware
 */
final class ProfilerMiddlewareTest extends TestCase
{
    /**
     * @covers ::process
     */
    public function testWithoutDispatcher(): void
    {
        $profiler = $this->mockProfiler();

        $factory = $this->createMock(FactoryInterface::class);
        $factory->method('make')->willReturn($profiler);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $interceptor = new ProfilerInterceptor(
            $factory,
            $container,
            $this->createMock(EnvironmentInterface::class),
        );
        $core = $this->createMock(CoreInterface::class);
        $interceptor->process('foo', 'bar', [], $core);

        self::assertCount(1, $profiler->tagsList);
        self::assertArrayHasKey('dispatcher', $tags = $profiler->tagsList[0]);
        self::assertNull($tags['dispatcher']);
    }

    private function mockProfiler(): object
    {
        return new class () {
            public function __construct(
                public array $tagsList = []
            ) {
            }

            public function start(array $ignoredFunctions = []): void
            {
            }

            public function end(array $tags = []): array
            {
                $this->tagsList[] = $tags;
                return [];
            }
        };
    }
}
