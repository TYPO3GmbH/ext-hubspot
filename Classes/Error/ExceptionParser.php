<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Error;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ExceptionParser - Get more details out of exceptions
 */
class ExceptionParser
{
    /**
     * Loops through exceptions to set message to underlying cause of error
     *
     * @param \Exception $badRequest
     * @return string
     */
    public function getBadRequestMessage(\Exception $badRequest): string
    {
        $exception = $badRequest;
        while ($exception->getPrevious() instanceof \Exception) {
            $exception = $exception->getPrevious();
        }
        if ($exception instanceof ClientException && $exception->getResponse() instanceof ResponseInterface) {
            $response = $exception->getResponse();
            // rewind stream
            $response->getBody()->seek(0);
            $contents = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $message = $contents['message'];
        } else {
            $message = $exception->getMessage();
        }
        return $message;
    }
}
