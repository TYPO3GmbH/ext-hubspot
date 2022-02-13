<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Exception;

use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Base for all contact synchronization exceptions.
 *
 * @see ContactSynchronizationService
 */
abstract class AbstractSynchronizationException extends \Exception
{
}
