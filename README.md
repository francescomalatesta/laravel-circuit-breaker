# Laravel Circuit Breaker

An implementation of the Circuit Breaker pattern for Laravel Framework 5.8.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-code-quality]][link-code-quality]
[![StyleCI](https://styleci.io/repos/130022855/shield?branch=master)](https://styleci.io/repos/130022855)

If you need an easy to use implementation of the [circuit breaker pattern](https://martinfowler.com/bliki/CircuitBreaker.html) for your Laravel application, you're in the right place.

**Note:** this package is compatible with Laravel 5.8. Other/previous versions are not tested yet.

## Install

You can use Composer to install the package for your project.

``` bash
$ composer require francescomalatesta/laravel-circuit-breaker
```

Don't worry about service providers and façades: Laravel can auto discover the package without doing nothing!

Just remember to **publish the config file** with

```php
php artisan vendor:publish
```

## Usage

You will always use a single class (`CircuitBreaker` façade or `CircuitBreakerManager` class if you want to inject it) to work with this package.

Here's the methods reference:

### isAvailable(string $identifier) : bool

Returns `true` if the `$identifier` service is currently available. Returns `false` otherwise.

**Note:** you can use whatever you want as identifier. I like to use the `MyClass::class` name when possible.

### reportFailure(string $identifier) : void

Reports a failed attempt for the `$identifier` service. Take a look at the Configuration section below to know how attempts and failure times are managed.

### reportSuccess(string $identifier) : void

Reports a successful attempt for the `$identifier` service. You can use it to mark a service as available and remove the "failed" status from it.

## Configuration

### Defaults

By editing the `config/circuit_breaker.php` config file contents you will able to tweak the circuit breaker in a way that is more suitable for your needs.

You have three values under the `default` item:

```php
<?php

return [
    'defaults' => [
        'attempts_threshold' => 3,
        'attempts_ttl' => 1,
        'failure_ttl' => 5
    ],
    
    // ...
];
```

- **attempts_threshold**: use it to specify how many attempts you have to make before declaring a service "failed" - default: 3;
- **attempts_ttl**: use to specify the time (in minutes) window in which the attempts are made before declaring a service "failed" - default: 1;
- **failure_ttl**: once a service is marked as "failed", it will remain in this status for this number of minutes - default: 5;

For a better understanding: by default, 3 failed attempts in 1 minute will result in a "failed" service for 5 minutes.

### Service Map

Tweaking the config file is cool, but what if I need to have specific ttl and attempts count for a specific service? No problem: the `services` option is here to help.

As you can see in the `config/circuit_breaker.php` config file, you also have a `services` item. You can specify settings for a single service here. Here's an example:

```php
<?php

return [
    'defaults' => [
        'attempts_threshold' => 3,
        'attempts_ttl' => 1,
        'failure_ttl' => 5
    ],
    
    'services' => [
        'my_special_service_identifier' => [
            'attempts_threshold' => 2,
            'attempts_ttl' => 1,
            'failure_ttl' => 10
        ]
    ]
];
```

Then, when you will call `CircuitBreaker::reportFailure('my_special_service_identifier')`, the circuit breaker will recognize the "special" service and use specific configuration settings, TTLs and attempts count.

**Protip:** you can also *overwrite* a single settings for a service in the `service` array. The others are going to be merged with the defaults.

## Usage Example

Let's assume we have a payments gateway integration for our application. We will call this class `PaymentsGateway`.

Now, let's also assume this is a third party service: sometimes it could be down for a while. However, we don't want to stop our users from buying something, so if the `PaymentsGateway` service is not available we want to redirect orders to a fallback service named `DelayedPaymentsGateway` that will simply "queue" delayed orders to process them in the future.

Let's stub this process in the following `BuyArticleOperation` class.

```php
<?php

class BuyArticleOperation {
    
    /** @var PaymentsGateway */
    private $paymentsGateway;
    
    /** @var DelayedPaymentsGateway */
    private $delayedPaymentsGateway;
    
    public function process(string $orderId)
    {
       // doing stuff with my order and then...
       
       try {
           $this->paymentsGateway->attempt($orderId);
       } catch (PaymentsGatewayException $e) {
           // something went wrong, let's switch the payment
           // to the "delayed" queue system
           $this->delayedPaymentsGateway->queue($orderId);
       }
    }
}
```

That's great! Now we are 100% sure that our payments are going to be processed. Sometimes that's not enough.

You know, maybe the `PaymentsGateway` takes at least 5 seconds for a single attempt, and your application receives hundreds of orders every minute. Is it really helpful to repeatedly call the `PaymentsGateway` even if we "know" it's not working after the first attempt? 

Well, this is how you can write your code with this circuit breaker.

```php
<?php

use CircuitBreaker;
use My\Namespace\PaymentsGateway;
use My\Namespace\DelayedPaymentsGateway;

class BuyArticleOperation {
    
    /** @var PaymentsGateway */
    private $paymentsGateway;
    
    /** @var DelayedPaymentsGateway */
    private $delayedPaymentsGateway;
    
    public function process(string $orderId)
    {
        if(CircuitBreaker::isAvailable(PaymentsGateway::class)) {
            try {
                $this->paymentsGateway->attempt($orderId);
            } catch (PaymentsGatewayException $e) {
                // something went wrong, let's switch the payment
                // to the "delayed" queue system and report that
                // the default gateway is not working!
                $this->delayedPaymentsGateway->queue($orderId);
                CircuitBreaker::reportFailure(PaymentsGateway::class);
            }
            
            // there's nothing we can do here anymore
            return;
        }
        
        // we already know that the service is disabled, so we
        // can queue the payment process on the delayed queue
        // directly, without letting our users wait more
        $this->delayedPaymentsGateway->queue($orderId);
    }
}
```

Let's assume we are processing 100 orders (on different processes) in 10 seconds.

* for the first order we ask the `PaymentsGateway` to handle that, but something goes wrong;
* we queue the payment on the `DelayedPaymentsGateway` and we report a failure to the `CircuitBreaker`;
* the same goes for the 2nd and 3rd orders;
* after the third order is processed, the `CircuitBreaker` decides that after 3 attempts (and in less than 1 minute) the `PaymentsGateway` can be declared "failed" and we can use directly our `DelayedPaymentsGateway` fallback for a while (5 minutes);
* the remaining 97 orders are queued and processed successfully, without wasting time (97 * 5 = 485 processing seconds) on a service we are quite sure that will not work;

Cool, huh? :)

## Testing

You can easily execute tests with

``` bash
$ vendor/bin/phpunit
```

## Coming Soon

* exponential backoff for failure TTLs;
* set TTLs less than 1 minute;
* make underlying store implementation customizable;

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email francescomalatesta@live.it instead of using the issue tracker.

## Credits

- [Francesco Malatesta][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/francescomalatesta/laravel-circuit-breaker.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/francescomalatesta/laravel-circuit-breaker/master.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/francescomalatesta/laravel-circuit-breaker.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/francescomalatesta/laravel-circuit-breaker
[link-travis]: https://travis-ci.org/francescomalatesta/laravel-circuit-breaker
[link-scrutinizer]: https://scrutinizer-ci.com/g/francescomalatesta/laravel-circuit-breaker/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/francescomalatesta/laravel-circuit-breaker
[link-downloads]: https://packagist.org/packages/francescomalatesta/laravel-circuit-breaker
[link-author]: https://github.com/francescomalatesta
[link-contributors]: ../../contributors
