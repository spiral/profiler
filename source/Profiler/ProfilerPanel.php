<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Profiler;

use Spiral\Core\Bootloaders\Bootloader;
use Spiral\Core\FactoryInterface;
use Spiral\Debug\BenchmarkerInterface;
use Spiral\Debug\Debugger;
use Spiral\Http\HttpDispatcher;

/**
 * Only contain publishable resources and configs.
 */
class ProfilerPanel extends Bootloader
{
    /**
     * Bootable!
     */
    const BOOT = true;

    /**
     * @var array
     */
    protected $bindings = [
        //To enable profiling
        BenchmarkerInterface::class => Debugger::class
    ];

    /**
     * @param HttpDispatcher   $dispatcher
     * @param FactoryInterface $factory
     */
    public function boot(HttpDispatcher $dispatcher, FactoryInterface $factory)
    {
        $dispatcher->pushMiddleware($factory->make(ProfilerWrapper::class, [
            'started' => microtime(true)
        ]));
    }
}