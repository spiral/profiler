<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Profiler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Container;
use Spiral\Core\ContainerInterface;
use Spiral\Core\Core;
use Spiral\Debug\BenchmarkerInterface;
use Spiral\Debug\Debugger;
use Spiral\Http\MiddlewareInterface;
use Spiral\Modules\DefinitionInterface;
use Spiral\Modules\Prototypes\Module;
use Spiral\Views\ConfigWriters\ViewConfig;
use Spiral\Views\ViewManager;

/**
 * Profiler middleware can be mounted in http component, it will enable memory logging, benchmarking
 * and render profiler panel at the bottom of the screen if possible,.
 */
class Profiler extends Module implements MiddlewareInterface
{
    /**
     * Constants used to describe benchmark records.
     */
    const BENCHMARK_CALLER = 0;
    const BENCHMARK_RECORD = 1;
    const BENCHMARK_CONTEXT = 2;
    const BENCHMARK_STARTED = 3;
    const BENCHMARK_ENDED = 4;

    /**
     * @var ViewManager
     */
    protected $view = null;

    /**
     * @var Debugger
     */
    protected $debugger = null;

    /**
     * @invisible
     * @var Container
     */
    protected $container = null;

    /**
     * @param ViewManager $view
     * @param Debugger    $debugger
     * @param Core        $container
     */
    public function __construct(ViewManager $view, Debugger $debugger, Core $container)
    {
        $this->view = $view;
        $this->debugger = $debugger;
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Closure $next
    )
    {
        if ($request->getAttribute('profiler'))
        {
            //Already handled at top level
            return $next();
        }

        //Mounting debugger scope
        $outerBenchmark = $this->container->replace(BenchmarkerInterface::class, $this->debugger);

        $started = microtime(true);
        $response = $next($request->withAttribute('profiler', $started));
        $elapsed = microtime(true) - $started;

        //Mounting profiler panel
        $response = $this->mount($request, $response, $started, $elapsed);

        //Restoring default benchmarker
        $this->container->restore($outerBenchmark);

        return $response;
    }

    /**
     * Mount profiler panel to response (if possible).
     *
     * @param ServerRequestInterface $request Server request instance.
     * @param ResponseInterface      $response
     * @param float                  $started Time when profiler was activated.
     * @param float                  $elapsed Elapsed time.
     * @return ResponseInterface
     */
    protected function mount(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $started = 0.0,
        $elapsed = 0.0
    )
    {
        if (!$response->getBody()->isWritable())
        {
            //We can't write to the stream
            return $response;
        }

        if (!empty($response->getHeaderLine('Content-Type')))
        {
            if (strpos($response->getHeaderLine('Content-Type'), 'html') === false)
            {
                //We can only write to responses when content type does not specified or responses
                //with html related content type
                return $response;
            }
        }

        $panel = $this->view->render('profiler:panel', [
            'profiler' => $this,
            'request'  => $request,
            'response' => $response,
            'started'  => $started,
            'elapsed'  => $elapsed
        ]);

        $response->getBody()->write($panel);

        return $response;
    }

    /**
     * Benchmarks will be returned in normalized form.
     *
     * @param float $lastEnding Last recorded time.
     * @return array|null
     */
    public function getBenchmarks(&$lastEnding = null)
    {
        $result = [];
        foreach ($this->debugger->getBenchmarks() as $record => $benchmark)
        {
            if (!isset($benchmark[self::BENCHMARK_ENDED]))
            {
                //Closing continues record
                $benchmark[self::BENCHMARK_ENDED] = microtime(true);
            }

            $elapsed = $benchmark[self::BENCHMARK_ENDED] - $benchmark[self::BENCHMARK_STARTED];

            $result[$record] = [
                'caller'  => $benchmark[self::BENCHMARK_CALLER],
                'record'  => $benchmark[self::BENCHMARK_RECORD],
                'context' => $benchmark[self::BENCHMARK_CONTEXT],
                'started' => $benchmark[self::BENCHMARK_STARTED],
                'ended'   => $lastEnding = $benchmark[self::BENCHMARK_ENDED],
                'elapsed' => $elapsed
            ];
        }

        return $result;
    }

    /**
     * Get list of global log messages (handled by default Logger).
     *
     * @return array
     */
    public function logMessages()
    {
        return $this->debugger->globalMessages();
    }

    /**
     * Format message based on log container name.
     *
     * @param string $container
     * @param string $message
     * @param array  $context
     * @return string
     */
    public function formatMessage($container, $message, $context)
    {
        \SqlFormatter::$pre_attributes = '';
        if (strpos($container, 'Spiral\Database\Drivers') === 0 && isset($context['query']))
        {
            //SQL queries from drivers
            return $this->highlightSQL($message);
        }

        return $message;
    }

    /**
     * Highlight SQL syntax.
     *
     * @param string $sql
     * @return string
     */
    public function highlightSQL($sql)
    {
        \SqlFormatter::$pre_attributes = '';

        return trim(substr(\SqlFormatter::highlight($sql), 6, -6));
    }

    /**
     * {@inheritdoc}
     */
    public static function getInstaller(
        ContainerInterface $container,
        DefinitionInterface $definition
    )
    {
        $installer = parent::getInstaller($container, $definition);

        /**
         * @var ViewConfig $viewConfig
         */
        $viewConfig = $container->construct(ViewConfig::class, [
            'baseDirectory' => $definition->getLocation()
        ]);

        //Profiler need it's views
        $installer->registerConfig($viewConfig->registerNamespace('profiler', 'source/views'));

        //
        $installer->addBinding('images', self::class);

        //And public resources
        $installer->publishDirectory('public');

        return $installer;
    }
}