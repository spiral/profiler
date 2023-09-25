<?php

declare(strict_types=1);

namespace Spiral\Profiler\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Boot\Environment;
use Spiral\Core\FactoryInterface;
use Spiral\Profiler\ProfilerMiddleware;

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

        $middleware = new ProfilerMiddleware(
            $factory,
            $container,
            new Environment()
        );
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $middleware->process($request, $handler);

        self::assertCount(1, $profiler->tagsList);
        self::assertArrayHasKey('dispatcher', $tags = $profiler->tagsList[0]);
        self::assertNull($tags['dispatcher']);
    }

    /**
     * @covers ::process
     * @dataProvider dataWithHttpHeaders
     */
    public function testWithHttpHeaders(bool|int|string $env, array $headers, bool $shouldBeCalled = true): void
    {
        $profiler = $this->mockProfiler();

        $factory = $this->createMock(FactoryInterface::class);
        $factory->method('make')->willReturn($profiler);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $middleware = new ProfilerMiddleware(
            $factory,
            $container,
            new Environment(['PROFILER_MIDDLEWARE_DEFAULT_ENABLED' => $env])
        );
        $request = new ServerRequest('GET', '/foo/bar', $headers);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $middleware->process($request, $handler);

        self::assertCount(
            $shouldBeCalled ? 1 : 0,
            $profiler->tagsList
        );
    }

    public static function dataWithHttpHeaders(): iterable
    {
        yield 'env = "true"; no headers' => [
            'true',
            [],
            true
        ];

        yield 'env = "1"; no headers' => [
            '1',
            [],
            true
        ];

        yield 'env = 1; no headers' => [
            1,
            [],
            true
        ];

        yield 'env = 0; no headers' => [
            0,
            [],
            false
        ];

        yield 'env = "0"; no headers' => [
            '0',
            [],
            false
        ];

        yield 'env = "false"; no headers' => [
            'false',
            [],
            false
        ];

        yield 'env = "true"; headers = "false"' => [
            'true',
            ['X-Spiral-Profiler-Enable' => 'false'],
            false
        ];

        yield 'env = "false"; headers = "true"' => [
            'false',
            ['X-Spiral-Profiler-Enable' => 'true'],
            true
        ];

        yield 'env = "false"; headers = "false"' => [
            'false',
            ['X-Spiral-Profiler-Enable' => 'false'],
            false
        ];

        yield 'env = "true"; headers = "true"' => [
            'false',
            ['X-Spiral-Profiler-Enable' => 'true'],
            true
        ];

        yield 'env = "0"; headers = "1"' => [
            '0',
            ['X-Spiral-Profiler-Enable' => '1'],
            true
        ];
    }

    private function mockProfiler(): object
    {
        return new class () {
            public function __construct(
                public array $tagsList = []
            )
            {
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
