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
use Spiral\Core\ContainerInterface;
use Spiral\Debug\Benchmarker;
use Spiral\Http\MiddlewareInterface;
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
     * @invisible
     * @var DebugHandler
     */
    private $handler = null;

    /**
     * @invisible
     * @var ViewsInterface
     */
    protected $views = null;

    /**
     * @invisible
     * @var Benchmarker
     */
    protected $benchmarker = null;

    /**
     * @invisible
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @param float              $started
     * @param ViewsInterface     $views
     * @param DebugHandler       $handler
     * @param Benchmarker        $benchmarker
     * @param ContainerInterface $container
     */
    public function __construct(
        float $started,
        ViewsInterface $views,
        DebugHandler $handler,
        Benchmarker $benchmarker,
        ContainerInterface $container
    ) {
        $this->started = $started;
        $this->handler = $handler;

        $this->views = $views;
        $this->benchmarker = $benchmarker;

        //Needed for correct scopes
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

        $response = $next($request->withAttribute('profiler', $this->started));
        $elapsed = microtime(true) - $this->started;

        //Mounting profiler panel
        return $this->mountProfiler($request, $response, $this->started, $elapsed);
    }

    /**
     * Mount profiler panel to response (if possible).
     *
     * @param Request  $request Server request instance.
     * @param Response $response
     * @param float    $started Time when profiler was activated.
     * @param float    $elapsed Elapsed time.
     *
     * @return Response
     */
    protected function mountProfiler(
        Request $request,
        Response $response,
        float $started = 0.0,
        float $elapsed = 0.0
    ): Response {
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

        $response->getBody()->write(
        //Rendering profiler panel
            $this->views->render('profiler:panel', [
                'profiler'  => $this,
                'container' => $this->container,
                'request'   => $request,
                'response'  => $response,
                'started'   => $started,
                'elapsed'   => $elapsed
            ])
        );

        return $response;
    }

    /**
     * Benchmarks will be returned in normalized form.
     *
     * @param float $lastEnding Last recorded time.
     *
     * @return array
     */
    public function getBenchmarks(float &$lastEnding = null): array
    {
        $result = [];
        foreach ($this->benchmarker->getBenchmarks() as $record => $benchmark) {
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
    public function logMessages(): array
    {
        return $this->handler->getRecords();
    }

    /**
     * Format message based on log container name.
     *
     * @param string $container
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    public function formatMessage(string $container, string $message, array $context): string
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
     *
     * @return string
     */
    public function highlightSQL(string $sql): string
    {
        \SqlFormatter::$pre_attributes = '';

        return trim(substr(\SqlFormatter::highlight($sql), 6, -6));
    }
}