<?php

namespace Gorkaio\Pimp\Tests;

use Gorkaio\Pimp\ConfigValidator;

class ConfigValidatorTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ConfigValidator
     */
    protected $sut;

    protected function setup()
    {
        $this->sut = new ConfigValidator();
    }

    public function testIsValidCalledWithSomethingNotArrayWillReturnFalse()
    {
        $isValid = $this->sut->isValid('kjhkjh');
        $this->assertFalse($isValid);
        $this->assertContains("Config should be an array", $this->sut->getErrors());
    }

    public function testIsValidCalledWithUnknownConfigKeysWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(),
                'option' => 23          // Misspelled 'options' key
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains("Unknown config key 'option'", $this->sut->getErrors());
    }

    public function testIsValidCalledWithConfigWithoutServicesKeyWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'params' => array(
                    'param1' => 23
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains("Config should contain 'services' key", $this->sut->getErrors());
    }

    public function testIsValidCalledWithServiceWithNoClassKeyWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'someService' => array()
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains("Class undefined for service 'someService'", $this->sut->getErrors());
    }

    public function testIsValidCalledWithServiceWithNonExistentClassWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\MyFakeClass'
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Class 'Gorkaio\\Pimp\\Tests\\MyFakeClass' for service 'someService' not found",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithNonArrayServiceParamsWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService',
                        'params' => 'This is not a valid params value'
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid params validating service 'someService': params should be an array",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithMissingRequiredServiceParamsWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService'
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid params validating service 'someService': required parameters missing",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceRequiringItselfWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@someService',
                            23
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains("Recursive dependency in service 'someService'", $this->sut->getErrors());
    }

    public function testIsValidCalledWithServiceRequiringUndefinedServiceWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@undefinedService',
                            23
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Service 'someService' requires undefined service 'undefinedService'",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceRequiringUndefinedParamWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService',
                            '~undefinedParam'
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Service 'someService' requires undefined param 'undefinedParam'",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceSettersSetAndNotArrayWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService',
                            '~undefinedParam'
                        ),
                        'setters' => 'This is not a valid setters config'
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid setters validating service 'someService': setters should be an array",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceSetterWithNonArrayParamsWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService',
                            '~undefinedParam'
                        ),
                        'setters' => array(
                            'setService' => 'This is not a valid setter params array'
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid setter configuration for service 'someService': 'setService' params should be an array",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceSetterAndSetterNotExistsWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService',
                            '~undefinedParam'
                        ),
                        'setters' => array(
                            'setMeNot' => array()
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid setter configuration for service 'someService': 'setMeNot' method does not exist",
            $this->sut->getErrors()
        );
    }


    public function testIsValidCalledWithSetterAndSetterMissingRequiredParamsWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'someService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
                        'params' => array(
                            '@simpleService',
                            '~undefinedParam'
                        ),
                        'setters' => array(
                            'setService' => array(3)
                        )
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid setter configuration for service 'someService': 'setService' required parameters missing",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithServiceWithOptionsAndOptionsNotArrayWillReturnFalse()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'simpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService',
                        'options' => 'Not a valid options array'
                    )
                )
            )
        );
        $this->assertFalse($isValid);
        $this->assertContains(
            "Invalid options validating service 'simpleService': options should be an array",
            $this->sut->getErrors()
        );
    }

    public function testIsValidCalledWithValidConfigWillReturnTrue()
    {
        $isValid = $this->sut->isValid(
            array(
                'services' => array(
                    'SimpleService' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\SimpleService'
                    ),
                    'ServiceWithSimpleDependency' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithSimpleDependency',
                        'params' => array('@SimpleService')
                    ),
                    'ServiceWithMixedDependencies' => array(
                        'class' => 'Gorkaio\Pimp\Tests\Fixtures\ServiceWithMixedDependencies',
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
        $this->assertTrue($isValid);
        $this->assertEmpty($this->sut->getErrors());
    }
}

