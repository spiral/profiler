<?php

declare(strict_types=1);

namespace Spiral\Profiler;

use Psr\Container\ContainerInterface;
use Spiral\Boot\DispatcherInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Debug\State;
use Spiral\Debug\StateInterface;
use SpiralPackages\Profiler\Profiler;

final class ProfilerInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly ContainerInterface $container,
        private readonly DispatcherInterface $dispatcher,
        private readonly EnvironmentInterface $env
    ) {
    }

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $profiler = $this->factory->make(Profiler::class, [
            'appName' => $this->env->get('PROFILER_APP_NAME', 'Spiral'),
        ]);

        $profiler->start();

        try {
            return $core->callAction($controller, $action, $parameters);
        } finally {
            $state = $this->container->has(StateInterface::class)
                ? $this->container->get(StateInterface::class)
                : new State();

            $tags = \array_merge($state->getTags(), [
                'controller' => $controller,
                'action' => $action,
                'dispatcher' => $this->dispatcher::class,
            ]);

            $profiler->end($tags);
        }
    }
}