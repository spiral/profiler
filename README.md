# Spiral Xhprof Profiler

[![PHP Version Require](https://poser.pugx.org/spiral/profiler/require/php)](https://packagist.org/packages/spiral/profiler)
[![Latest Stable Version](https://poser.pugx.org/spiral/profiler/v/stable)](https://packagist.org/packages/spiral/profiler)
[![phpunit](https://github.com/spiral/profiler/actions/workflows/phpunit.yml/badge.svg)](https://github.com/spiral/profiler/actions)
[![psalm](https://github.com/spiral/profiler/actions/workflows/psalm.yml/badge.svg)](https://github.com/spiral/profiler/actions)
[![Codecov](https://codecov.io/gh/spiral/profiler/branch/master/graph/badge.svg)](https://codecov.io/gh/spiral/profiler/)
[![Total Downloads](https://poser.pugx.org/spiral/profiler/downloads)](https://packagist.org/packages/spiral/profiler)
[![StyleCI](https://github.styleci.io/repos/447581540/shield)](https://github.styleci.io/repos/447581540)
<a href="https://discord.gg/8bZsjYhVVk"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+
- Spiral framework 3.0+

## Installation

To install the package:

```bash
composer require spiral/profiler
```

After package install you need to add bootloader from the package in your application.

```php
use Spiral\RoadRunnerBridge\Bootloader as RoadRunnerBridge;

protected const LOAD = [
    // ...
    Spiral\Profiler\ProfilerBootloader::class,
    // ...
];
```

Define env variables:

```dotenv
PROFILER_ENABLE=true
PROFILER_ENDPOINT=http://127.0.0.1:8080/api/profiler/store
PROFILER_APP_NAME="My super app"
PROFILER_MIDDLEWARE_DEFAULT_ENABLED=true
```

## Usage

There are two ways to use profiler:

- Profiler as a middleware
- Profiler as an interceptor

### Profiler as an interceptor

Interceptor will be useful if you want to profile some specific part of your application which supports using interceptors.
 - Controllers, 
 - GRPC, 
 - Queue jobs,
 - TCP
 - Events.

```php
namespace App\Bootloader;

use App\Interceptor\CustomInterceptor;
use Spiral\Bootloader\DomainBootloader;
use Spiral\Core\CoreInterface;

class AppBootloader extends DomainBootloader
{
    protected const SINGLETONS = [
        CoreInterface::class => [self::class, 'domainCore']
    ];

    protected const INTERCEPTORS = [
        \Spiral\Profiler\ProfilerInterceptor::class
    ];
}
```

> Read more about interceptors [here](https://spiral.dev/docs/cookbook-domain-core/3.3/en).

### Profiler as a middleware

To use profiler as a middleware you need to add it to your router.

#### Global middleware

```php
namespace App\Bootloader;

use Spiral\Bootloader\Http\RoutesBootloader as BaseRoutesBootloader;
use Spiral\Profiler\ProfilerMiddleware;

final class RoutesBootloader extends BaseRoutesBootloader
{
    protected function globalMiddleware(): array
    {
        return [
            ProfilerMiddleware::class,  // <================
            LocaleSelector::class,
            ErrorHandlerMiddleware::class,
            JsonPayloadMiddleware::class,
            HttpCollector::class,
        ];
    }
    
    // ...
}
```

#### Route group middleware

```php
namespace App\Bootloader;

use Spiral\Bootloader\Http\RoutesBootloader as BaseRoutesBootloader;
use Spiral\Profiler\ProfilerMiddleware;

final class RoutesBootloader extends BaseRoutesBootloader
{
    // ...

    protected function middlewareGroups(): array
    {
        return [
            'web' => [
                CookiesMiddleware::class,
                SessionMiddleware::class,
                CsrfMiddleware::class,
            ],
            'profiler' => [                  // <================
                ProfilerMiddleware::class,
                'middleware:web',
            ],
        ];
    }
    
    // ...
}
```

#### Route middleware

```php
class HomeController implements SingletonInterface
{
    #[Route(route: '/', name: 'index.page', methods: ['GET'], middleware: 'profiler')]
    public function index(...): void 
    {
        // ...
    }
    
    #[Route(route: '/', name: 'index.page', methods: ['GET'], middleware: \Spiral\Profiler\ProfilerMiddleware::class)]
    public function index(...): void 
    {
        // ...
    }
}
```

#### Profiling strategy.

By default, middleware start profiling on every request. Н
You can configure profiling to be enabled only for certain requests.

1. Set env variable PROFILER_MIDDLEWARE_DEFAULT_ENABLED to false.
```dotenv
PROFILER_MIDDLEWARE_DEFAULT_ENABLED=false
```

2. Pass Http header `X-Spiral-Profiler-Enable=1` for request you want to profile.
