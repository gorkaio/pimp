# Pimp: Simple Dependency Injection Container
_Pimp_ is a simple dependency injection container implementation in PHP

[![Build Status](https://travis-ci.org/glopezdetorre/pimp.svg?branch=master)](https://travis-ci.org/glopezdetorre/pimp)
[![Code Coverage](https://scrutinizer-ci.com/g/glopezdetorre/pimp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/glopezdetorre/pimp/?branch=master)

## Installation
Using _composer_, inside your project root run:

    composer require gorkaio/pimp

## Container
Simple use case:

    use Gorkaio\Pimp\Container;

    $config = array(
        'services' =>
            array(
                'RoadRunner' => array(
                    'class' => 'Acme\Services\RoadRunnerService'
                )
            )
    );

    $container = new Container($config);
    $roadRunner = $container->get('RoadRunner');

By default container initialization will validate configuration to check if defined services, params and options are
valid. Config validation is quite thorough, so you might want to disable it on production environments once everything
looks ok on devel:

    $useValidation = $this->isDevelEnvironment();
    $container = new Container($config, $useValidation);


## Config
Service dependencies are defined as an array:

    $config = array(
        array(
            'services' => array(
                'SimpleService' => array(
                    'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                ),
                'ServiceWithSimpleDependency' => array(
                    'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                    'params' => array(
                        '@SimpleService'
                    )
                ),
                'ServiceWithMixedDependencies' => array(
                    'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithMixedDependencies',
                    'params' => array(
                        '@SimpleService',
                        '~param1',
                        '@ServiceWithSimpleDependency',
                        '~param2',
                        42
                    ),
                    'setters' => array(
                        'setParam' => array(1, '~param1')
                    ),
                    'options' => array(
                        'scope' => 'singleton'
                    )
                )
            ),
            'params' => array(
                'param1' => 23,
                'param2' => 'myParamValue'
            )
        )
    );

    $container = new Container($config);
    $myService = $container->get('ServiceWithSimpleDependency');

_services_ key holds service definitions. _params_ holds parameters.

## Services
Each service definition is declared as follows:

    '<serviceName>' => array(
        'class' => '<serviceClass>',
        'params' =>
            array(
                '@serviceDependency1',
                '@serviceDependency2',
                ...,
                '~configParam1',
                '~configParam2',
                ...
                ...
                'staticValue',
                42
            ),
        'setters' =>
            array(
                'setServiceParam' => array(
                    '@serviceDependency1'
                    '~configParam1',
                    42
                )
            ),
        'options' =>
            array(
                'scope': <prototype|singleton>
            )
        )
    )

- _serviceName_ is the name that will be used by the Container to retrieve this service.
- _class_ is the fully qualified name of the service class
- _params_ (optional) the ordered params needed by the service constructor, where:
	- a param preceded by `@` is a reference to another service name
	- a param preceded by `~` is a reference to a config param
	- if neither of the above precede the param value, it is passed to the constructor as a literal value
- _setters_ (optional) service setter methods that will be called after service instantiation. Same rules apply for its params as for those of the service.
- _options_ (optional) the Container options for this service, where:
	- 'scope' option defines whether the service will be instanced as a singleton or a prototype. That is, if the Container should return the same instance or a new one each time `get()` is called. 'prototype' will be used by default.

## Params
Simple _(key, value)_ pairs used by service definitions above.

    $config = array(
        'services' => array(
            ...
        ),
        'params' => array(
            'param1' => 23,
            'param2' => 'myParamValue'
        )
    );

## Todo
- Detect recursive dependencies beyond the self-reference
- Allow service param escaping. Right now, it would not be possible to use literals '@me' or '~you' as a param value,
as they would be parsed as service reference and param reference values.
- Improve constructor and setter signature validation