<?php

namespace Gorkaio\Pimp;

use ReflectionClass;
use Gorkaio\Pimp\Exceptions\ServiceException;
use Gorkaio\Pimp\Exceptions\ServiceNotFoundException;

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
     * @param $config
     */
    public function __construct($config)
    {
        if ($this->validateConfig($config)) {
            $this->config = $config;
        }
    }

    /**
     * Get service instance by name
     *
     * @param $serviceName
     * @throws ServiceException
     * @throws ServiceNotFoundException
     * @return mixed
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
     * @param $serviceName
     * @return object
     * @throws ServiceException
     */
    private function getNewInstance($serviceName)
    {

        try {
            $serviceClassName = $this->config['services'][$serviceName]['class'];

            $serviceParameters = isset($this->config['services'][$serviceName]['params'])
                ?$this->config['services'][$serviceName]['params']
                :array();

            // Service instantiation
            $paramValues = $this->parseParameters($serviceParameters);
            if (count($paramValues) > 0) {
                $r = new ReflectionClass($serviceClassName);
                $service = $r->newInstanceArgs($paramValues);
            } else {
                $service = new $serviceClassName;
            }

            // Call defined setters
            $serviceSetters = isset($this->config['services'][$serviceName]['setters'])
                ?$this->config['services'][$serviceName]['setters']
                :array();

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
     * Parses service dependencies and params
     *
     * @param $serviceParameters
     * @return array
     * @throws ServiceException
     * @throws ServiceNotFoundException
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

    /**
     * Validates Pimp configuration
     *
     * @param $config
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function validateConfig($config)
    {
        foreach ($config['services'] as $serviceName => $serviceConfig) {
            if (!isset($serviceConfig['class']) || empty($serviceConfig['class'])) {
                throw new \InvalidArgumentException(
                    "Class undefined for service '{$serviceName}'"
                );
            }

            if (!class_exists($serviceConfig['class'])) {
                throw new \InvalidArgumentException(
                    "Class '{$serviceConfig['class']}' for service '{$serviceName}' not found"
                );
            }

            if (!(
                $this->validateServiceParams($serviceName, $config) &&
                $this->validateServiceSetters($serviceName, $config) &&
                $this->validateServiceOptions($serviceName, $config)
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates service dependencies and params
     *
     * - If service params is defined, it must be null or an array
     * - If the service has a service dependency, this service must be defined in the configuration
     * - If the service has a param dependency, this param must be defined in the configuration
     * - Services cannot define themselves as a dependency
     * - @todo Recursive dependencies should also be detected
     *
     * @param $serviceName
     * @param $config
     * @return bool
     */
    private function validateServiceParams($serviceName, $config)
    {
        if (!isset($config['services']) || !array_key_exists($serviceName, $config['services'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        $serviceConfig = $config['services'][$serviceName];
        if (!isset($serviceConfig['params']) || $serviceConfig['params'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['params'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        $serviceParams = $serviceConfig['params'];
        foreach ($serviceParams as $serviceParameter) {
            if (preg_match("/^@(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    throw new \InvalidArgumentException("Invalid configuration of '{$serviceName}'");
                } else {
                    if ($serviceName == $matches[1]) {
                        throw new \InvalidArgumentException("Recursive dependency in service '{$serviceName}'");
                    }

                    if (!array_key_exists($matches[1], $config['services'])) {
                        throw new \InvalidArgumentException(
                            "Service '{$serviceName}' requires undefined service '{$matches[1]}'"
                        );
                    }
                }
            } elseif (preg_match("/^~(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    throw new \InvalidArgumentException("Invalid configuration of '{$serviceName}'");
                } else {
                    if (!isset($config['params']) ||
                        !is_array($config['params']) ||
                        !array_key_exists($matches[1], $config['params'])
                    ) {
                        throw new \InvalidArgumentException(
                            "Service '{$serviceName}' requires undefined param '{$matches[1]}'"
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * Validates service configuration options
     *
     * @param $serviceName
     * @param $config
     * @return bool
     */
    private function validateServiceOptions($serviceName, $config)
    {
        if (!isset($config['services']) || !array_key_exists($serviceName, $config['services'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        $serviceConfig = $config['services'][$serviceName];
        if (!isset($serviceConfig['options']) || $serviceConfig['options'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['options'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        foreach ($serviceConfig['options'] as $optionName => $optionValue) {
            // @todo: obvious overkill until we refactor this and add other options
            if (!in_array($optionName, array('scope'))) {
                throw new \InvalidArgumentException(
                    "Unknown option '{$optionName}' validating service '{$serviceName}'"
                );
            }

            if (!$this->validateOption($optionName, $optionValue)) {
                throw new \InvalidArgumentException(
                    "Invalid option value for '{$optionName}' validating service '{$serviceName}'"
                );
            }
        }

        return true;
    }

    /**
     * Validates single service configuration option
     *
     * @param $optionName
     * @param $optionValue
     * @return bool
     */
    private function validateOption($optionName, $optionValue)
    {
        switch ($optionName) {
            case 'scope':
                $isValid = in_array($optionValue, array('singleton', 'prototype'));
                break;
            default:
                $isValid = false;
        }
        return $isValid;
    }

    /**
     * Validates service setters
     *
     * @param $serviceName
     * @param $config
     * @return bool
     */
    private function validateServiceSetters($serviceName, $config)
    {
        if (!isset($config['services']) || !array_key_exists($serviceName, $config['services'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        $serviceConfig = $config['services'][$serviceName];
        if (!isset($serviceConfig['setters']) || $serviceConfig['setters'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['setters'])) {
            throw new \InvalidArgumentException(
                "Invalid configuration found validating service '{$serviceName}'"
            );
        }

        $serviceSetters = $serviceConfig['setters'];
        $r = isset($r)?$r:new ReflectionClass($config['services'][$serviceName]['class']);

        foreach ($serviceSetters as $setterName => $setterParams) {
            if ($setterParams === null) {
                $setterParams = array();
            } elseif (!is_array($setterParams)) {
                throw new \InvalidArgumentException(
                    "Invalid '{$setterName}' setter configuration for service '{$serviceName}'"
                );
            }

            if (!$r->hasMethod($setterName) ||
                $r->getMethod($setterName)->getNumberOfRequiredParameters() !== count($setterParams)
            ) {
                throw new \InvalidArgumentException(
                    "Invalid '{$setterName}' setter configuration for service '{$serviceName}'"
                );
            }
        }
        return true;
    }
}
