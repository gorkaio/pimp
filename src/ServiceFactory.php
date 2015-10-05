<?php

namespace Gorka\Pimp;

class ServiceFactory
{
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * @param \Closure $closure
     */
    private function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param \Closure $closure
     * @return static
     */
    public static function create(\Closure $closure)
    {
        return new static ($closure);
    }

    public function getInstance(Container $container)
    {
        $closure = $this->closure;
        return $closure($container);
    }
}
