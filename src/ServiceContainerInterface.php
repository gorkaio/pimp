<?php
/**
 * Project: gorka.io
 * File: ServiceContainerInterface.php
 *
 * User: gorka
 * Date: 14/10/14
 */

namespace Gorkaio\Pimp;

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
