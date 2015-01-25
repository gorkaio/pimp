<?php

namespace Gorkaio\Pimp\Test\Fixtures;

class ServiceWithSimpleDependency {

    protected $service;

    public function __construct(SimpleService $service) {
        $this->service = $service;
    }

    public function getDependency()
    {
        return $this->service;
    }
}
