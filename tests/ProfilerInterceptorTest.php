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
 * @coversDefaultClass \Spiral\Profiler\ProfilerInterceptor
 */
final class ProfilerInterceptorTest extends TestCase
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

    /**
     * If ProfilerInterceptor::process calls several times the only top call starts and ends profiler.
     */
    public function testOnlyOneNestedStartEnd(): void
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

        $i = 10;
        $core = $this->createMock(CoreInterface::class);
        $core
            ->method('callAction')
            ->willReturnCallback(function () use ($interceptor, $core, &$i) {
                if ($i-- === 0) {
                    return null;
                }
                return $interceptor->process("foo$i", "bar$i", [], $core);
            });
        $interceptor->process('foo', 'bar', [], $core);
        // $profiler->start and $profiler->end must be called once
        self::assertCount(1, $profiler->tagsList);
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
