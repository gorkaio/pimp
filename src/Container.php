<?php

namespace Gorka\Pimp;

use Assert\Assertion;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Gorka\Pimp\Exception\ContainerException as PimpContainerException;
use Gorka\Pimp\Exception\NotFoundException as EntryNotFoundException;

/**
 * Class Container
 * @package Gorka\Pimp
 */
class Container implements ContainerInterface
{
    /**
     * @var \Closure[] Closures array by name
     */
    private $closures = [];

    /**
     * @var object[] Instances
     */
    private $instances = [];

    /**
     * @var ServiceFactory[] Factories array by name
     */
    private $factories = [];

    /**
     * @var string[] Registered service/factory names
     */
    private $registeredIds = [];

    /**
     * Constructor
     *
     * You may provide an array of services to the initialization:
     *
     * $container = new Container([
     *      'service' => function ($c) { return new MyService(); },
     *      'factory' => ServiceFactory::create(function($c) { return new MyOtherService($c->get('service')); } )
     * ]);
     *
     * @param array|null $entries
     * @throws PimpContainerException
     */
    public function __construct($entries = null)
    {
        if (null === $entries) {
            $entries = [];
        }

        foreach ($entries as $id => $entry) {
            $this->add($id, $entry);
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new EntryNotFoundException(sprintf('Service with id \'%s\' not found', $id));
        }

        if (isset($this->instances[$id])) {
            $instance = $this->instances[$id];
        } elseif (isset($this->closures[$id])) {
            $this->instances[$id] = $this->closures[$id]($this);
            $instance = $this->instances[$id];
        } else {
            $instance = $this->factories[$id]->getInstance($this);
        }

        return $instance;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for
     *
     * @return boolean
     */
    public function has($id)
    {
        return (in_array($id, $this->registeredIds));
    }

    /**
     * Adds a service with the given id
     *
     * @param string $id Identifier of the entry
     * @param \Closure|ServiceFactory $entry Entry itself or closure to get an instance of it
     *
     * @throws ContainerException
     */
    public function add($id, $entry)
    {
        try {
            $this->guardEntryId($id);
            $this->guardEntry($entry);
        } catch (\Exception $e) {
            throw new PimpContainerException($e->getMessage());
        }

        $this->registeredIds[] = $id;
        if ($entry instanceof ServiceFactory) {
            unset($this->closures[$id], $this->instances[$id]);
            $this->factories[$id] = $entry;
        } else {
            unset($this->factories[$id], $this->instances[$id]);
            $this->closures[$id] = $entry;
        }
    }

    /**
     * Validate entry ID
     *
     * Entry ID should be a non empty, non blank, string
     *
     * @param mixed $id
     *
     * @throws \InvalidArgumentException
     */
    private function guardEntryId($id)
    {
        Assertion::string($id, 'Id should be a string');
        Assertion::notBlank(trim($id), 'Id cannot be blank');
    }

    /**
     * Validate entry
     *
     * Entry should be an object or callable
     *
     * @param mixed $entry
     *
     * @throws \InvalidArgumentException
     */
    private function guardEntry($entry)
    {
        if (!(($entry instanceof \Closure) || ($entry instanceof ServiceFactory))) {
            throw new \InvalidArgumentException('Entry must be a Closure or ServiceFactory');
        }
    }
}
