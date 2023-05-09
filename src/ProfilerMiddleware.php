<?php

declare(strict_types=1);

namespace Spiral\Profiler;

use Psr\Container\ContainerInterface;
use Spiral\Boot\DispatcherInterface;
use Spiral\Boot\EnvironmentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Debug\State;
use Spiral\Debug\StateInterface;
use SpiralPackages\Profiler\Profiler;

final class ProfilerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly ContainerInterface $container,
        private readonly EnvironmentInterface $env,
        private readonly ?DispatcherInterface $dispatcher = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $profiler = $this->factory->make(Profiler::class, [
            'appName' => $this->env->get('PROFILER_APP_NAME', 'Spiral'),
        ]);

        $profiler->start();

        try {
            return $handler->handle($request);
        } finally {
            $state = $this->container->has(StateInterface::class)
                ? $this->container->get(StateInterface::class)
                : new State();

            $tags = \array_merge($state->getTags(), [
                'route' => $request->getAttribute('route.name'),
                'uri' => (string)$request->getUri(),
                'dispatcher' => $this->dispatcher ? $this->dispatcher::class : null,
            ]);

            $profiler->end($tags);
        }
    }
}
