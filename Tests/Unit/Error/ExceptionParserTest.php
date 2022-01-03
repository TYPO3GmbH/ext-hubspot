<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Tests\Unit\Error;

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
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Error\ExceptionParser;

class ExceptionParserTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function getBadRequestMessagesReturnsGuzzleClientResponseMessage()
    {
        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $stream = $this->prophesize(Stream::class);
        $stream->getContents()->willReturn(json_encode(['message' => 'foo bar']));
        $response->getBody()->willReturn($stream->reveal());
        $response->getStatusCode()->willReturn(400);

        $clientException = new ClientException('', $request->reveal(), $response->reveal());
        $badRequest = new BadRequest('', 123, $clientException);

        $exceptionParser = new ExceptionParser();
        $badRequestMessage = $exceptionParser->getBadRequestMessage($badRequest);

        self::assertSame('foo bar', $badRequestMessage);
    }

    /**
     * @test
     * @return void
     */
    public function getBadRequestMessageReturnsInnerExceptionMessageIfNoClientInformationAvailable()
    {
        $invalidArgumentException = new \InvalidArgumentException('some message');
        $badRequest = new BadRequest('', 123, $invalidArgumentException);

        $exceptionParser = new ExceptionParser();
        $badRequestMessage = $exceptionParser->getBadRequestMessage($badRequest);

        self::assertSame('some message', $badRequestMessage);
    }

    /**
     * @test
     * @return void
     */
    public function getBadRequestMessageReturnsMainExceptionMessageIfNoInnerExceptionGiven()
    {
        $badRequest = new BadRequest('foomp!');

        $exceptionParser = new ExceptionParser();
        $badRequestMessage = $exceptionParser->getBadRequestMessage($badRequest);

        self::assertSame('foomp!', $badRequestMessage);
    }
}
