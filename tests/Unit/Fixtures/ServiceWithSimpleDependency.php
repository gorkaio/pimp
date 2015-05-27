<?php

namespace Tests\Gorka\Pimp\Fixtures;

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
