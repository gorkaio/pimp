<?php
/**
 * Created by IntelliJ IDEA.
 * User: gorka
 * Date: 28/1/15
 * Time: 19:50
 */

namespace Gorka\Pimp;

/**
 * Class ConfigValidator
 *
 * @package Gorkaio\Pimp
 */
class ConfigValidator
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * Validates Pimp configuration
     *
     * @param array $config Services and params configuration
     * @return bool True if configuration is valid, false otherwise
     * @throws \InvalidArgumentException
     */
    public function isValid($config)
    {
        if (!is_array($config)) {
            $this->addError("Config should be an array");
            return false;
        }

        $unknownConfigKeys = array_diff(array_keys($config), array('services', 'params'));
        if (count($unknownConfigKeys) > 0) {
            foreach ($unknownConfigKeys as $unknownConfigKey) {
                $this->addError("Unknown config key '{$unknownConfigKey}'");
            }
            return false;
        }

        if (!array_key_exists('services', $config)) {
            $this->addError("Config should contain 'services' key");
            return false;
        }

        foreach ($config['services'] as $serviceName => $serviceConfig) {
            if ($this->validateServiceClass($serviceName, $config)) {
                $this->validateServiceParams($serviceName, $config);
                $this->validateServiceSetters($serviceName, $config);
                $this->validateServiceOptions($serviceName, $config);
            }
        }

        return (count($this->errors) == 0);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $description
     */
    protected function addError($description)
    {
        $this->errors[] = $description;
    }

    /**
     * Gets service configuration
     *
     * @param $serviceName string Service name
     * @param $config array Pimp configuration array
     * @return mixed
     * @throws \RuntimeException
     */
    private function getServiceConfig($serviceName, $config)
    {
        if (!isset($config['services'][$serviceName])) {
            throw new \RuntimeException("Config validator required a non existing service name");
        }
        return $config['services'][$serviceName];
    }

    /**
     * Validates class definition for a service
     *
     * - Class key defined
     * - Class exists
     *
     * @param $serviceName string Service Name
     * @param $config array Pimp config
     * @return bool True if service class definition is valid
     */
    private function validateServiceClass($serviceName, $config)
    {
        $serviceConfig = $this->getServiceConfig($serviceName, $config);

        if (!isset($serviceConfig['class']) || empty($serviceConfig['class'])) {
            $this->addError("Class undefined for service '{$serviceName}'");
            return false;
        }

        if (!class_exists($serviceConfig['class'])) {
            $this->addError("Class '{$serviceConfig['class']}' for service '{$serviceName}' not found");
            return false;
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
     * - Service configured params should match class constructor's signature
     * - @todo: Recursive dependencies should also be detected
     * - @todo: Service class signature is only checked for required number of params right now
     *
     * @param string $serviceName Service name
     * @param array $config Services and params configuration
     * @return bool True if configuration is valid, false otherwise
     */
    private function validateServiceParams($serviceName, $config)
    {
        $serviceConfig = $this->getServiceConfig($serviceName, $config);
        if (!isset($serviceConfig['params']) || $serviceConfig['params'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['params'])) {
            $this->addError("Invalid params validating service '{$serviceName}': params should be an array");
            return false;
        }

        $serviceParams = $serviceConfig['params'];
        $r = new \ReflectionClass($serviceConfig['class']);
        if ($r->getConstructor()->getNumberOfRequiredParameters() > count($serviceParams)) {
            $this->addError("Invalid params validating service '{$serviceName}': required parameters missing");
            return false;
        }

        $isValid = true;
        foreach ($serviceParams as $serviceParameter) {
            if (preg_match("/^@(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    $this->addError("Invalid configuration of '{$serviceName}'");
                    return false;
                } else {
                    if ($serviceName == $matches[1]) {
                        $this->addError("Recursive dependency in service '{$serviceName}'");
                        $isValid = false;
                        break;
                    }
                    if (!array_key_exists($matches[1], $config['services'])) {
                        $this->addError("Service '{$serviceName}' requires undefined service '{$matches[1]}'");
                        $isValid = false;
                        break;
                    }
                }
            } elseif (preg_match("/^~(.*)/", $serviceParameter, $matches)) {
                if (!isset($matches[1])) {
                    $this->addError("Invalid configuration of '{$serviceName}'");
                    return false;
                } else {
                    if (!isset($config['params']) ||
                        !is_array($config['params']) ||
                        !array_key_exists($matches[1], $config['params'])
                    ) {
                        $this->addError("Service '{$serviceName}' requires undefined param '{$matches[1]}'");
                        $isValid = false;
                        break;
                    }
                }
            }
        }

        return $isValid;
    }

    /**
     * Validates service configuration options
     *
     * @param string $serviceName Service name
     * @param array $config Services and params configuration
     * @return bool True if configuration is valid, false otherwise
     */
    private function validateServiceOptions($serviceName, $config)
    {
        $serviceConfig = $this->getServiceConfig($serviceName, $config);
        if (!isset($serviceConfig['options']) || $serviceConfig['options'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['options'])) {
            $this->addError("Invalid options validating service '{$serviceName}': options should be an array");
            return false;
        }

        $optionValidators = $this->getOptionValidators();

        $isValid = true;
        foreach ($serviceConfig['options'] as $optionName => $optionValue) {
            if (!array_key_exists($optionName, $optionValidators)) {
                $this->addError("Unknown option '{$optionName}' validating service '{$serviceName}'");
                $isValid = false;
                break;
            }

            if (!$optionValidators[$optionName]($optionValue)) {
                $this->addError("Invalid option value for '{$optionName}' validating service '{$serviceName}'");
                $isValid = false;
                break;
            }
        }

        return $isValid;
    }

    /**
     * Returns an array where key is option name and value a validation function
     *
     * @return array
     */
    private function getOptionValidators()
    {
        $optionValidators = array(
            'scope' => function ($value) {
                return in_array($value, array(Container::SERVICE_SCOPE_SINGLETON, Container::SERVICE_SCOPE_PROTOTYPE));
            }
        );

        return $optionValidators;
    }

    /**
     * Validates service setters
     *
     * @param string $serviceName Service name
     * @param array $config Services and params configuration
     * @return bool True if configuration is valid, false otherwise
     */
    private function validateServiceSetters($serviceName, $config)
    {
        $serviceConfig = $this->getServiceConfig($serviceName, $config);
        if (!isset($serviceConfig['setters']) || $serviceConfig['setters'] === null) {
            return true;
        } elseif (!is_array($serviceConfig['setters'])) {
            $this->addError("Invalid setters validating service '{$serviceName}': setters should be an array");
            return false;
        }

        $serviceSetters = $serviceConfig['setters'];
        $r = isset($r)?$r:new \ReflectionClass($config['services'][$serviceName]['class']);

        $isValid = true;
        foreach ($serviceSetters as $setterName => $setterParams) {
            if ($setterParams === null) {
                $setterParams = array();
            } elseif (!is_array($setterParams)) {
                $this->addError(
                    "Invalid setter configuration for service '{$serviceName}': '{$setterName}'"
                    ." params should be an array"
                );
                $isValid = false;
                break;
            }

            if (!$r->hasMethod($setterName)) {
                $this->addError(
                    "Invalid setter configuration for service '{$serviceName}': '{$setterName}' method does not exist"
                );
                break;
            }

            if ($r->getMethod($setterName)->getNumberOfRequiredParameters() > count($setterParams)) {
                $this->addError("Invalid setter configuration for service '{$serviceName}': '{$setterName}'"
                    ." required parameters missing");
                $isValid = false;
            }
        }

        return $isValid;
    }
}
