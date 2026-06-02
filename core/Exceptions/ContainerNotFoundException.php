<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class ContainerNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
