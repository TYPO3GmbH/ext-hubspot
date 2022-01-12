<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Exception;

use SevenShores\Hubspot\Exceptions\HubspotException;

/**
 * Thrown if a hubspot contact can't be found even if we know (because Hubspot says so) that it should be there
 */
class UnexpectedMissingContactException extends HubspotException
{
}
