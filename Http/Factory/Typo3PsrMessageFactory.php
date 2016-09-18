<?php declare(strict_types=1);

/*
 * This file is part of the BartacusBundle.
 *
 * The BartacusBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The BartacusBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the BartacusBundle.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\Http\Factory;

use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TYPO3\CMS\Core\Http\Response as Typo3Response;
use TYPO3\CMS\Core\Http\Stream as Typo3Stream;

/**
 * Builds Psr\HttpMessage instances using the TYPO3 implementation.
 *
 * Based on the Symfony PSR Message bridge.
 */
class Typo3PsrMessageFactory implements HttpMessageFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(Request $symfonyRequest)
    {
        throw new \BadMethodCallException('createRequest is not implemented nor will it in the future');
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(Response $symfonyResponse):ResponseInterface
    {
        if ($symfonyResponse instanceof BinaryFileResponse) {
            $stream = new Typo3Stream($symfonyResponse->getFile()->getPathname(), 'r');
        } else {
            $stream = new Typo3Stream('php://temp', 'rw');
            if ($symfonyResponse instanceof StreamedResponse) {
                ob_start(function ($buffer) use ($stream) {
                    $stream->write($buffer);

                    return false;
                });

                $symfonyResponse->sendContent();
                ob_end_clean();
            } else {
                $stream->write($symfonyResponse->getContent());
            }
        }

        $headers = $symfonyResponse->headers->all();

        $cookies = $symfonyResponse->headers->getCookies();
        if (!empty($cookies)) {
            $headers['Set-Cookie'] = [];

            foreach ($cookies as $cookie) {
                $headers['Set-Cookie'][] = (string) $cookie;
            }
        }

        $response = new Typo3Response(
            $stream,
            $symfonyResponse->getStatusCode(),
            $headers
        );

        $protocolVersion = $symfonyResponse->getProtocolVersion();
        if ('1.1' !== $protocolVersion) {
            $response = $response->withProtocolVersion($protocolVersion);
        }

        return $response;
    }
}
