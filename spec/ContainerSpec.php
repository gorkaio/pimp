<?php

namespace spec\Gorka\Pimp;

use Assert\Assertion;
use Gorka\Pimp\Container;
use Gorka\Pimp\ServiceFactory;
use Interop\Container\Exception\ContainerException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Interop\Container\ContainerInterface;

class ContainerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Container::class);
    }

    function it_should_implement_container_interop_interface()
    {
        $this->shouldImplement(ContainerInterface::class);
    }

    function it_should_throw_not_found_exception_getting_nonexisting_entries()
    {
        $this->shouldThrow(ContainerException::class)->during('get', ['dontCare']);
    }

    function it_should_allow_adding_and_retrieving_services()
    {
        $this->add('service', function () { return new \StdClass(); });
        $this->get('service')->shouldBeAnInstanceOf(\StdClass::class);
        $this->add('factory', ServiceFactory::create(function () { return new \StdClass(); }));
        $this->get('factory')->shouldBeAnInstanceOf(\StdClass::class);
    }

    function it_should_not_allow_adding_nonstring_or_empty_ids()
    {
        $invalidServiceIds = [
            null,
            '',
            '   ',
            "\n",
            23,
            new \StdClass()
        ];

        foreach ($invalidServiceIds as $invalidServiceId) {
            $this->shouldThrow(ContainerException::class)->during('add', [$invalidServiceId, new \StdClass()]);
        }
    }

    function it_should_not_allow_adding_non_closures()
    {
        $invalidServices = [
            null,
            '',
            23,
            'myService',
            new \StdClass()
        ];

        foreach ($invalidServices as $invalidService) {
            $this->shouldThrow(ContainerException::class)->during('add', ['serviceName', $invalidService]);
        }
    }

    function it_should_allow_knowing_if_service_is_loaded()
    {
        $this->has('service')->shouldBe(false);
        $this->has('factory')->shouldBe(false);
        $this->add('service', function () { return new \StdClass(); });
        $this->add('factory', ServiceFactory::create(function () { return new \StdClass(); }));
        $this->has('service')->shouldBe(true);
        $this->has('factory')->shouldBe(true);
    }

    function it_should_allow_overwriting_services()
    {
        $this->add('service', function() { return null; });
        $this->add('service', ServiceFactory::create(function(){ return new \StdClass(); }));
        $this->get('service')->shouldBeAnInstanceOf(\StdClass::class);
    }

    /**
     * @todo: try to rewrite this in a cleaner way
     */
    function it_should_call_closures_just_once()
    {
        $counter = 0;
        $this->add('service', function() use(&$counter) { ++$counter; return new Fake($counter); });
        $this->get('service')->counter()->shouldBe(1);
        $this->get('service')->counter()->shouldBe(1);
    }

    function it_should_be_initializable_with_an_array_of_entries()
    {
        $this->beConstructedWith([
            'service' => function () { return new \StdClass(); },
            'serviceFactory' => ServiceFactory::create(function () { return new \StdClass(); })
        ]);

        $this->get('service')->shouldBeAnInstanceOf(\StdClass::class);
        $this->get('serviceFactory')->shouldBeAnInstanceOf(\StdClass::class);
    }
}

class Fake {

    private $counter;

    public function __construct($counter = 0) {
        $this->counter = $counter;
    }

    public function counter()
    {
        return $this->counter;
    }
}
