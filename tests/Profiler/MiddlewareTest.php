<?php
/**
 * profiler
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\Profiler;

use Spiral\Http\Routing\Route;
use Spiral\Profiler\ProfilerBootloader;
use Spiral\Tests\HttpTest;

class MiddlewareTest extends HttpTest
{
    public function testNoMiddleware()
    {
        $this->http->addRoute(new Route(
            'default',
            '/',
            'TestApplication\Controllers\DefaultController:index'
        ));

        $result = $this->get('/');
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame("Hello, Dave.", $result->getBody()->__toString());
    }

    public function testWithMiddleware()
    {
        $this->http->addRoute(new Route(
            'default',
            '/',
            'TestApplication\Controllers\DefaultController:index'
        ));

        $this->app->getBootloader()->bootload([
            ProfilerBootloader::class
        ]);

        $result = $this->get('/');
        $this->assertSame(200, $result->getStatusCode());
        $this->assertContains("Spiral Environment", $result->getBody()->__toString());
    }
}