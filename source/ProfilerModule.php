<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral;

use Spiral\Core\DirectoriesInterface;
use Spiral\Modules\ModuleInterface;
use Spiral\Modules\PublisherInterface;
use Spiral\Modules\RegistratorInterface;

class ProfilerModule implements ModuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(RegistratorInterface $registrator)
    {
        $registrator->configure('views', 'namespaces', 'spiral/profiler', [
            "'profiler' => [",
            "   directory('libraries') . 'spiral/profiler/source/views/',",
            "   /*{{namespaces.profiler}}*/",
            "]"
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function publish(PublisherInterface $publisher, DirectoriesInterface $directories)
    {
        /**
         * Publishing all module visual resources. We are going to overwrite existed files.
         */
        $publisher->publishDirectory(
            __DIR__ . '/../resources',                        //Profiler js, css and modules
            $directories->directory('public') . 'resources',  //Expected directory in webroot
            PublisherInterface::OVERWRITE                     //We can safely overwrite resources
        );
    }
}