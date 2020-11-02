<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository\Exception;

use SevenShores\Hubspot\Exceptions\HubspotException;

/**
 * Thrown if the backend user doesn't have the necessary permissions to modify records
 */
class DataHandlerErrorException extends HubspotException
{
}
