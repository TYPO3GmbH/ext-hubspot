<?php
declare(strict_types=1);
namespace T3G\Hubspot\Error;

/*
 * This file is part of TYPO3 GmbHs software toolkit.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * The TYPO3 project - inspiring people to share!
 */

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;


/**
 * Class ExceptionParser - Get more details out of exceptions
 *
 * @package T3G\Hubspot\Error
 */
class ExceptionParser
{
    /**
     * Loops through exceptions to set message to underlying cause of error
     *
     * @param \Exception $badRequest
     *
     * @return string
     */
    public function getBadRequestMessage(\Exception $badRequest): string {
        $exception = $badRequest;
        while ($exception->getPrevious() instanceof \Exception) {
            $exception = $exception->getPrevious();
        }
        if ($exception instanceof ClientException && $exception->getResponse() instanceof ResponseInterface) {
            $response = $exception->getResponse();
            // rewind stream
            $response->getBody()->seek(0);
            $contents = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            $message = $contents['message'];
        } else {
            $message = $exception->getMessage();
        }
        return $message;
    }
}