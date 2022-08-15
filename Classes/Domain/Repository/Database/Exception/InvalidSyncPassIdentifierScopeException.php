<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Database\Exception;

/**
 * An exception thrown if the difference between the max and min of FrontendUserRepository's pass identifier is larger
 * than 1 or max is smaller than min.
 */
class InvalidSyncPassIdentifierScopeException extends \UnexpectedValueException
{
}
