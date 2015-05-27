<?php
/**
 * Project: Pimp
 * File: ServiceContainerInterface.php
 * @license MIT
 */

namespace Gorka\Pimp;

/**
 * Interface ServiceContainerInterface
 * @package Pimp
 */
interface ServiceContainerInterface
{

    /**
     * Returns instance of given service
     *
     * @param $serviceName
     * @return mixed
     */
    public function get($serviceName);
}
