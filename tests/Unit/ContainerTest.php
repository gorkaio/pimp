<?php

namespace Tests\Gorka\Pimp;

use Gorka\Pimp\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Container
     */
    protected $sut;

    public function testContainerInitializedWithValidationOffWillNotValidateConfig()
    {
        $this->sut = new Container('This is not a valid config', false);
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithUnexistingServiceClassThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\UnexistingService'
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithMisconfiguredServiceThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService',
                        'params' => 'ThisShouldBeAnArray'
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithServiceWithUnknownServiceDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithServiceWithUnknownParamDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('~someValue')
                    )
                ),
                'params' => array('anotherValue' => 5)
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithServiceWithUndefinedParamDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('~someValue')
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithServiceWithImmediateDependencyRecursionThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@ServiceWithSimpleDependency')
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Gorka\Pimp\Exceptions\InvalidConfigException
     */
    public function testContainerInitializedWithInvalidScopeOptionWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService',
                        'options' => array(
                            'scope' => 'invalidScope'
                        )
                    )
                )
            )
        );
    }

    public function testGetCalledWithAnExistingSimpleServiceReturnsInstanceOfService()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService'
                    )
                )
            )
        );

        $this->assertInstanceOf('Tests\Gorka\Pimp\Fixtures\SimpleService', $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithAnExistingServiceWithSimpleDependencyWillReturnService() {

        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    )
                )
            )
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
            $this->sut->get('ServiceWithSimpleDependency')
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\SimpleService',
            $this->sut->get('ServiceWithSimpleDependency')->getDependency()
        );
    }

    public function testGetCalledWithServiceWithMixedDependenciesWillReturnService()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    ),
                    'ServiceWithMixedDependencies' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@SimpleService',
                            '~param1',
                            '@ServiceWithSimpleDependency',
                            '~param2',
                            42
                        )
                    )
                ),
                'params' => array(
                    'param1' => 23,
                    'param2' => 'myParamValue'
                )
            )
        );

        $serviceInstance = $this->sut->get('ServiceWithMixedDependencies');

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\ServiceWithMixedDependencies',
            $serviceInstance
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\SimpleService',
            $serviceInstance->getService(0)
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
            $serviceInstance->getService(1)
        );

        $this->assertEquals(23, $serviceInstance->getParam(0));
        $this->assertEquals('myParamValue', $serviceInstance->getParam(1));
        $this->assertEquals(42, $serviceInstance->getParam(2));
    }

    public function testGetCalledWithSingletonServiceWillReturnSameInstance()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService',
                        'options' => array('scope' => 'singleton')
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Tests\Gorka\Pimp\Fixtures\SimpleService', $instance);
        $this->assertSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithPrototypeServiceWillReturnNewInstance()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService',
                        'options' => array('scope' => 'prototype')
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Tests\Gorka\Pimp\Fixtures\SimpleService', $instance);
        $this->assertNotSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithNoScopeDefinedWillReturnNewInstance()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService'
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Tests\Gorka\Pimp\Fixtures\SimpleService', $instance);
        $this->assertNotSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithServiceWithSettersWillCallSettersAfterInstantitation()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    ),
                    'ServiceWithMixedDependencies' => array(
                        'class' => 'Tests\Gorka\Pimp\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@SimpleService',
                            '~param1'
                        ),
                        'setters' => array(
                            'setService' => array(1, '@ServiceWithSimpleDependency'),
                            'setParam' => array(1, '~param2')
                        )
                    )
                ),
                'params' => array(
                    'param1' => 23,
                    'param2' => 'myParamValue'
                )
            )
        );

        $serviceInstance = $this->sut->get('ServiceWithMixedDependencies');

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\ServiceWithMixedDependencies',
            $serviceInstance
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\SimpleService',
            $serviceInstance->getService(0)
        );

        $this->assertInstanceOf(
            'Tests\Gorka\Pimp\Fixtures\ServiceWithSimpleDependency',
            $serviceInstance->getService(1)
        );

        $this->assertEquals(23, $serviceInstance->getParam(0));
        $this->assertEquals('myParamValue', $serviceInstance->getParam(1));
        $this->assertNull($serviceInstance->getParam(2));
    }
}
