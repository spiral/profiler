<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Profiler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Core\Container;
use Spiral\Core\ContainerInterface;
use Spiral\Debug\Debugger;
use Spiral\Debug\Logger\SharedHandler;
use Spiral\Http\MiddlewareInterface;
use Spiral\Views\ViewManager;
use Spiral\Views\ViewsInterface;

/**
 * Profiler can be mounted in http pipeline, it will enable memory logging, benchmarking
 * and render profiler panel at the bottom of the screen if possible.
 */
class ProfilerWrapper implements MiddlewareInterface
{
    /**
     * Constants used to describe benchmark records.
     */
    const BENCHMARK_CALLER  = 0;
    const BENCHMARK_RECORD  = 1;
    const BENCHMARK_CONTEXT = 2;
    const BENCHMARK_STARTED = 3;
    const BENCHMARK_ENDED   = 4;

    /**
     * @var float|int
     */
    private $started = 0;

    /**
     * @var ViewManager
     */
    protected $view = null;

    /**
     * @var SharedHandler
     */
    protected $handler = null;

    /**
     * @var Debugger
     */
    protected $debugger = null;

    /**
     * @invisible
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @param float|int          $started
     * @param ViewsInterface     $views
     * @param Debugger           $debugger
     * @param ContainerInterface $container
     */
    public function __construct(
        $started,
        ViewsInterface $views,
        Debugger $debugger,
        ContainerInterface $container
    ) {
        $this->started = $started;
        $this->view = $views;
        $this->debugger = $debugger;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        if ($request->getAttribute('profiler')) {
            //Already handled at top level
            return $next($request, $response);
        }

        $outerHandler = $this->debugger->shareHandler($this->handler = new SharedHandler());

        try {
            $response = $next($request->withAttribute('profiler', $this->started));
            $elapsed = microtime(true) - $this->started;
        } finally {
            //Restoring original debug handler
            $this->debugger->shareHandler($outerHandler);
        }

        //Mounting profiler panel
        return $this->mountPanel($request, $response, $this->started, $elapsed);
    }

    /**
     * Mount profiler panel to response (if possible).
     *
     * @param Request  $request Server request instance.
     * @param Response $response
     * @param float    $started Time when profiler was activated.
     * @param float    $elapsed Elapsed time.
     * @return Response
     */
    protected function mountPanel(
        Request $request,
        Response $response,
        $started = 0.0,
        $elapsed = 0.0
    ) {
        if (!$response->getBody()->isWritable()) {
            //We can't write to the stream
            return $response;
        }

        if (!empty($response->getHeaderLine('Content-Type'))) {
            if (strpos($response->getHeaderLine('Content-Type'), 'html') === false) {
                //We can only write to responses when content type does not specified or responses
                //with html related content type
                return $response;
            }
        }

        $response->getBody()->write($this->view->render('profiler:panel', [
            'profiler'  => $this,
            'container' => $this->container,
            'request'   => $request,
            'response'  => $response,
            'started'   => $started,
            'elapsed'   => $elapsed
        ]));

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
        foreach ($this->debugger->getBenchmarks() as $record => $benchmark) {
            if (!isset($benchmark[self::BENCHMARK_ENDED])) {
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
        return $this->handler->getRecords();
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
        //We have to do interpolation first
        $message = \Spiral\interpolate($message, $context);

        \SqlFormatter::$pre_attributes = '';
        if (strpos($container, 'Spiral\Database\Drivers') === 0 && isset($context['query'])) {
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
}