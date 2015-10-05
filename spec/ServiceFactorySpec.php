<?php

namespace spec\Gorka\Pimp;

use Gorka\Pimp\Container;
use Gorka\Pimp\ServiceFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServiceFactorySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('create', [function(){ return new \StdClass(); }]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ServiceFactory::class);
    }

    function it_should_only_allow_closures()
    {
        $invalidValues = [
            '',
            null,
            23,
            new \StdClass()
        ];

        foreach($invalidValues as $invalidValue) {
            $this->shouldThrow(\Exception::class)->during('create', [$invalidValue]);
        }
    }

    function it_should_return_new_instances_on_call(Container $container)
    {
        $this->getInstance($container)->shouldBeAnInstanceOf(\StdClass::class);
        $this->getInstance($container)->shouldNotBe($this->getInstance($container));
    }
}
