# Pimp: Simple Dependency Injection Container
_Pimp_ is a PHP dependency injection container inspired by [Pimple](http://pimple.sensiolabs.org/),
with a [Container Interop](https://github.com/container-interop/container-interop) turn to it.

## Backwards compatibility note
Version 3.0.0 has introduced a major backwards compatibility break to comply with container interop specification. On
previous version of _Pimp_ config values could be introduced in the container itself; this is no longer possible, please use
a [config manager](https://github.com/glopezdetorre/config) instead.

[![Build Status](https://travis-ci.org/glopezdetorre/pimp.svg?branch=master)](https://travis-ci.org/glopezdetorre/pimp)
[![Code Coverage](https://scrutinizer-ci.com/g/glopezdetorre/pimp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/glopezdetorre/pimp/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/glopezdetorre/pimp/badges/quality-score.png?branch=master)](https://scrutinizer-ci.com/g/glopezdetorre/pimp/?branch=master)


## Installation

Using _composer_, inside your project root run:

    composer require gorka/pimp

## Container

Simple use case:

    use Gorka\Pimp\Container;
    use Gorka\Pimp\ServiceFactory;

    $container = new Container([
        'serviceOne' => function ($c) {
            return new MyService();
        },
        'serviceTwo' => function ($c) {
            return new MyOtherService($c->get('serviceOne'));
        }
    ]);
    
    $service = $container->get('serviceOne');

You may also add new services or service factories after initialization:

    $container->add('ServiceThree', function() { return new MyAwesomeService(); });

By default, _Pimp_ will return same instance each time you call _get()_. If you want to get a new instance each time
you may use a _ServiceFactory_ instead:

    $service1 = $container->get('ServiceOne');
    $service2 = $container->get('ServiceOne');
    var_dump($service1 === $service2); // true

    $container->add(
        'ServiceFour', 
        ServiceFactory::create(
            function($c) { 
                return new MyAwesomeService(); 
            }
        )
    );

    $service1 = $container->get('ServiceFour');
    $service2 = $container->get('ServiceFour');
    var_dump($service1 === $service2); // false

