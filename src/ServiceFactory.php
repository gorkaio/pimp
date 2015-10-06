<?php

namespace Gorka\Pimp;

/**
 * Class ServiceFactory
 * @package Gorka\Pimp
 */
class ServiceFactory
{
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * Constructor
     *
     * @param \Closure $closure
     */
    private function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Create ServiceFactory
     *
     * @param \Closure $closure
     *
     * @return static
     */
    public static function create(\Closure $closure)
    {
        return new static ($closure);
    }

    /**
     * Get service instance
     *
     * @param Container $container
     *
     * @return object
     */
    public function getInstance(Container $container)
    {
        $closure = $this->closure;
        return $closure($container);
    }
}
