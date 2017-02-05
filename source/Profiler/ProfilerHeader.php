<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Profiler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Core\Bootloaders\Bootloader;
use Spiral\Http\HttpDispatcher;

/**
 * Simply adds headers with elapsed time.
 */
class ProfilerHeader extends Bootloader
{
    /**
     * Has to be booted.
     */
    const BOOT = true;

    /**
     * @param HttpDispatcher $dispatcher
     */
    public function boot(HttpDispatcher $dispatcher)
    {
        $dispatcher->riseMiddleware(function (Request $request, Response $response, $next) {
            $start = microtime(true);

            /**
             * @var Response $response
             */
            $response = $next($request, $response);

            return $response->withHeader(
                'X-Elapsed',
                number_format(microtime(true) - $start, 4)
            );
        });
    }
}