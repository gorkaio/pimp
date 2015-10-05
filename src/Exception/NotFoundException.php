<?php

namespace Gorka\Pimp\Exception;

use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
