<?php

use Gorka\Pimp\Container;
use Gorka\Pimp\ServiceFactory;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;

/**
 * Created by IntelliJ IDEA.
 * User: gorka
 * Date: 4/10/15
 * Time: 8:53
 */
class ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $sut;

    protected function setup()
    {
        parent::setUp();
        $this->sut = new Container();
    }

    function testItIsInitializableWithAnArrayOfServiceAndServiceFactories()
    {
        $sut = new Container([
            'service' => function() { return new \StdClass(); },
            'serviceFactory' => ServiceFactory::create(function () { return new \StdClass(); } )
        ]);
        $this->assertInstanceOf(\StdClass::class, $sut->get('service'));
        $this->assertInstanceOf(\StdClass::class, $sut->get('serviceFactory'));
    }

    function testEntriesRegisteredWithClosuresReturnSameInstance()
    {
        $this->sut->add('testEntry', function() { return new \StdClass(); } );
        $this->assertSame($this->sut->get('testEntry'), $this->sut->get('testEntry'));
    }

    function testEntriesRegisteredWithFactoriesReturnNewInstances()
    {
        $this->sut->add(
            'testEntry',
            ServiceFactory::create(
                function() {
                    return new \StdClass();
                }
            )
        );
        $this->assertNotSame($this->sut->get('testEntry'), $this->sut->get('testEntry'));
    }

    function testRequestingNonExistintServicesThrowsException()
    {
        $this->setExpectedException(NotFoundException::class);
        $this->sut->get('Nemo');
    }

    function testTryingToAddNonClosuresThrowsException()
    {
        $badValues = [
            '',
            null,
            new \StdClass()
        ];

        foreach ($badValues as $badValue) {
            $this->setExpectedException(ContainerException::class);
            $this->sut->add('service', $badValue);
        }
    }

    function testHasReturnsWhetherServiceIsRegistered()
    {
        $this->assertFalse($this->sut->has('Nemo'));
        $this->sut->add('Nemo', function(){ return new \StdClass();});
        $this->assertTrue($this->sut->has('Nemo'));
    }
}
