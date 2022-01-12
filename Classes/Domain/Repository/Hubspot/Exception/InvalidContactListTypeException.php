<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Hubspot\Exception;

/**
 * Exception thrown if a Hubspot contact list is of the wrong type (e.g. dynamic when it should be static)
 */
class InvalidContactListTypeException extends \Exception
{
}
