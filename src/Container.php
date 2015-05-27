<?php
/**
 * Project: Pimp
 * File: Container.php
 * @license MIT
 */

namespace Gorka\Pimp;

use ReflectionClass;
use Gorka\Pimp\Exceptions\InvalidConfigException;
use Gorka\Pimp\Exceptions\ServiceException;
use Gorka\Pimp\Exceptions\ServiceNotFoundException;

/**
 * Class Container
 *
 * @package Gorkaio\Pimp
 */
class Container implements ServiceContainerInterface
{
    const SERVICE_SCOPE_PROTOTYPE = 'prototype';
    const SERVICE_SCOPE_SINGLETON = 'singleton';

    /**
     * @var array Service and params definitions
     */
    protected $config;

    /**
     * @var array Service instances for services with scope 'singleton'
     */
    protected $serviceInstances;

    /**
     * @param array $config Configuration array for services and params
     * @param bool $useValidation Validate config on container instantiation
     * @throws InvalidConfigException
     */
    public function __construct($config, $useValidation = true)
    {
        if ($useValidation) {
            $validator = new ConfigValidator();
            if (!$validator->isValid($config)) {
                throw new InvalidConfigException();
            }
            $this->config = $config;
        }
    }

    /**
     * Get service instance by name
     *
     * @param string $serviceName Service name
     * @throws ServiceException
     * @throws ServiceNotFoundException
     * @return object Service instance
     */
    public function get($serviceName)
    {
        if (isset($this->serviceInstances[$serviceName])) {
            $service = $this->serviceInstances[$serviceName];
        } else {
            $service = $this->getNewInstance($serviceName);

            $scope = isset($this->config['services'][$serviceName]['options']['scope'])
                ?$this->config['services'][$serviceName]['options']['scope']
                :self::SERVICE_SCOPE_PROTOTYPE;

            if ($scope == self::SERVICE_SCOPE_SINGLETON) {
                $this->serviceInstances[$serviceName] = $service;
            }
        }

        return $service;
    }

    /**
     * Returns a new service instance
     *
     * @param string $serviceName Service name
     * @return object Instance of the service requested
     * @throws ServiceException
     */
    private function getNewInstance($serviceName)
    {
        try {
            $serviceConfig = $this->config['services'][$serviceName];
            if (isset($serviceConfig['class']) && $serviceConfig['class'] != '') {
                $serviceClassName = $serviceConfig['class'];
            } else {
                throw new \InvalidArgumentException('Invalid config');
            }

            $serviceParameters = isset($serviceConfig['params'])?$serviceConfig['params']:array();

            // Service instantiation
            $paramValues = $this->parseParameters($serviceParameters);
            if (count($paramValues) > 0) {
                $r = new ReflectionClass($serviceClassName);
                $service = $r->newInstanceArgs($paramValues);
            } else {
                $service = new $serviceClassName;
            }

            // Call defined setters
            $serviceSetters = isset($serviceConfig['setters'])?$serviceConfig['setters']:array();

            foreach ($serviceSetters as $setterName => $setterParams) {
                if ($setterParams === null) {
                    $setterParams = array();
                }
                call_user_func_array(
                    array($service, $setterName),
                    $this->parseParameters($setterParams)
                );
            }

        } catch (\Exception $e) {
            throw new ServiceException(
                sprintf("Unable to start service '%s': %s", $serviceName, $e->getMessage())
            );
        }

        return $service;
    }

    /**
     * Parses service dependencies and params and returns an array usable for a callable
     *
     * @param array $serviceParameters 'params' sub-array of the service configuration
     * @return array Array of params usable for a callable method
     * @throws ServiceException
     * @throws ServiceNotFoundException
     * @todo: allow parameter escaping for literals
     */
    private function parseParameters($serviceParameters)
    {
        $paramValues = array();
        foreach ($serviceParameters as $serviceParameter) {
            if (preg_match("/^@(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    throw new \InvalidArgumentException();
                } else {
                    $paramValues[] = $this->get($matches[1]);
                }
            } elseif (preg_match("/^~(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    throw new \InvalidArgumentException();
                } else {
                    $paramValues[] = $this->config['params'][$matches[1]];
                }
            } else {
                $paramValues[] = $serviceParameter;
            }
        }
        return $paramValues;
    }
}
