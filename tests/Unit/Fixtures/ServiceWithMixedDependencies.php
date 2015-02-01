<?php

namespace Gorkaio\Pimp\Tests\Fixtures;

class ServiceWithMixedDependencies {

    protected $services;

    protected $params;

    public function __construct(
        SimpleService $service1,
        $param1,
        ServiceWithSimpleDependency $service2 = null,
        $param2 = null,
        $param3 = null
    ) {
        $this->services[] = $service1;
        $this->services[] = $service2;
        $this->params[] = $param1;
        $this->params[] = $param2;
        $this->params[] = $param3;
    }

    public function getService($index) {
        return $this->services[$index];
    }

    public function getParam($index) {
        return $this->params[$index];
    }

    public function setService($index, $service) {
        $this->services[$index] = $service;
    }

    public function setParam($index, $paramValue) {
        $this->params[$index] = $paramValue;
    }
}