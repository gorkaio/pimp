<?php

namespace Gorkaio\Pimp\Test\Services;

use Gorkaio\Pimp\Container;
use Gorkaio\Pimp\Test\Fixtures\SimpleService;

class ContainerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Container
     */
    protected $sut;

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithUnexistingServiceClassThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\UnexistingService'
                    )
                )
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithMisconfiguredServiceThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService',
                        'params' => 'ThisShouldBeAnArray'
                    )
                )
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithServiceWithUnknownServiceDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    )
                )
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithServiceWithUnknownParamDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('~someValue')
                    )
                ),
                'params' => array('anotherValue' => 5)
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithServiceWithUndefinedParamDependencyWillThrowException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('~someValue')
                    )
                )
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerInitializedWithServiceWithImmediateDependencyRecursionThrowsException()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@ServiceWithSimpleDependency')
                    )
                )
            )
        );
    }

    public function testContainerInitializedWithInvalidScopeOptionWillThrowException()
    {
        array(
            'services' => array(
                'SimpleService' => array(
                    'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService',
                    'options' => array(
                        'scope' => 'invalidScope'
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
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                    )
                )
            )
        );

        $this->assertInstanceOf('Gorkaio\Pimp\Test\Fixtures\SimpleService', $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithAnExistingServiceWithSimpleDependencyWillReturnService() {

        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    )
                )
            )
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
            $this->sut->get('ServiceWithSimpleDependency')
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\SimpleService',
            $this->sut->get('ServiceWithSimpleDependency')->getDependency()
        );
    }

    public function testGetCalledWithServiceWithMixedDependenciesWillReturnService()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    ),
                    'ServiceWithMixedDependencies' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithMixedDependencies',
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
            'Gorkaio\Pimp\Test\Fixtures\ServiceWithMixedDependencies',
            $serviceInstance
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\SimpleService',
            $serviceInstance->getService(0)
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
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
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService',
                        'options' => array('scope' => 'singleton')
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Gorkaio\Pimp\Test\Fixtures\SimpleService', $instance);
        $this->assertSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithPrototypeServiceWillReturnNewInstance()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService',
                        'options' => array('scope' => 'prototype')
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Gorkaio\Pimp\Test\Fixtures\SimpleService', $instance);
        $this->assertNotSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithNoScopeDefinedWillReturnNewInstance()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                    )
                )
            )
        );

        $instance = $this->sut->get('SimpleService');
        $this->assertInstanceOf('Gorkaio\Pimp\Test\Fixtures\SimpleService', $instance);
        $this->assertNotSame($instance, $this->sut->get('SimpleService'));
    }

    public function testGetCalledWithServiceWithSettersWillCallSettersAfterInstantitation()
    {
        $this->sut = new Container(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    ),
                    'ServiceWithMixedDependencies' => array(
                        'class' => 'Gorkaio\Pimp\Test\Fixtures\ServiceWithMixedDependencies',
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
            'Gorkaio\Pimp\Test\Fixtures\ServiceWithMixedDependencies',
            $serviceInstance
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\SimpleService',
            $serviceInstance->getService(0)
        );

        $this->assertInstanceOf(
            'Gorkaio\Pimp\Test\Fixtures\ServiceWithSimpleDependency',
            $serviceInstance->getService(1)
        );

        $this->assertEquals(23, $serviceInstance->getParam(0));
        $this->assertEquals('myParamValue', $serviceInstance->getParam(1));
        $this->assertNull($serviceInstance->getParam(2));
    }
}
