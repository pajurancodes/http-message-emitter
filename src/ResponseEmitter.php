<?php

namespace PajuranCodes\Http\Message\Emitter;

use function header;
use function sprintf;
use function strtolower;
use function headers_sent;
use Psr\Http\Message\ResponseInterface;
use PajuranCodes\Http\Message\Emitter\ResponseEmitterInterface;

/**
 * An emitter of a HTTP response.
 *
 * @author pajurancodes
 */
class ResponseEmitter implements ResponseEmitterInterface {

    /**
     * @inheritDoc
     */
    public function emit(ResponseInterface $response): never {
        $this
            ->checkHeadersSent()
            // Should the status line be sent after the response headers?
            ->sendStatusLine($response)
            // Should the response headers be sent before the status line?
            ->sendResponseHeaders($response)
            ->outputBody($response)
        ;

        exit();
    }

    /**
     * Check if one or more headers were already sent.
     * 
     * @return static
     * @throws \RuntimeException One or more headers were already sent.
     */
    private function checkHeadersSent(): static {
        if (headers_sent()) {
            throw new \RuntimeException(
                    'Headers already sent. The '
                    . 'response could not be emitted.'
            );
        }

        return $this;
    }

    /**
     * Send the "status line" of a HTTP response.
     *
     * An existing "status line" header 
     * will be replaced by the current one.
     * 
     * @link https://tools.ietf.org/html/rfc7230#section-3.1.2 Hypertext Transfer Protocol 
     * (HTTP/1.1): Message Syntax and Routing > 3.1.2. Status Line
     * 
     * @param ResponseInterface $response A HTTP response.
     * @return static
     */
    private function sendStatusLine(ResponseInterface $response): static {
        $statusLine = sprintf('HTTP/%s %s %s'
            , $response->getProtocolVersion()
            , $response->getStatusCode()
            , $response->getReasonPhrase()
        );

        header($statusLine, true);

        return $this;
    }

    /**
     * Send the response headers of a HTTP response.
     * 
     * The response headers are found in 
     * the headers list of the HTTP response.
     * 
     * @link https://tools.ietf.org/html/rfc7230#section-3.2 Hypertext Transfer Protocol 
     * (HTTP/1.1): Message Syntax and Routing > 3.2. Header Fields
     * @link https://tools.ietf.org/html/rfc7231#section-7 Hypertext Transfer Protocol 
     * (HTTP/1.1): Semantics and Content > 7. Response Header Fields
     * @link https://tools.ietf.org/html/rfc6265#section-4.1 HTTP State 
     * Management Mechanism > 4.1. Set-Cookie
     * @link https://tools.ietf.org/html/rfc6265#section-5.2 HTTP State 
     * Management Mechanism > 5.2. The Set-Cookie Header
     *
     * @param ResponseInterface $response A HTTP response.
     * @return static
     */
    private function sendResponseHeaders(ResponseInterface $response): static {
        foreach ($response->getHeaders() as $name => $values) {
            strtolower($name) === 'set-cookie' ?
                    $this->sendSetCookieHeaders($name, $values) :
                    $this->sendSingleHeader(
                        $name,
                        $response->getHeaderLine($name)
                    )
            ;
        }

        return $this;
    }

    /**
     * Send a "Set-Cookie" header for each of the values in the given list.
     *
     * An existing header with the same name ("Set-Cookie", 
     * case-insensitive) will not be replaced. Instead, 
     * the current one will be added to it.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-4.1 HTTP State 
     * Management Mechanism > 4.1. Set-Cookie
     * @link https://tools.ietf.org/html/rfc6265#section-5.2 HTTP State 
     * Management Mechanism > 5.2. The Set-Cookie Header
     *
     * @param string $name The header name "Set-Cookie", case-insensitive.
     * @param string[] $values (optional) An indexed array with all string 
     * values of the "Set-Cookie" header.
     * @return static
     */
    private function sendSetCookieHeaders(string $name, array $values = []): static {
        foreach ($values as $value) {
            $responseHeader = sprintf('%s: %s', $name, $value);

            header($responseHeader, false);
        }

        return $this;
    }

    /**
     * Send a single response header.
     * 
     * The values of the header are concatenated to a 
     * comma-separated string, e.g. to a "header line".
     *
     * An existing header with the same name will not be 
     * replaced. Instead, the current one will be added to it.
     * 
     * @link https://tools.ietf.org/html/rfc7230#section-3.2 Hypertext Transfer Protocol 
     * (HTTP/1.1): Message Syntax and Routing > 3.2. Header Fields
     * @link https://tools.ietf.org/html/rfc7231#section-7 Hypertext Transfer Protocol 
     * (HTTP/1.1): Semantics and Content > 7. Response Header Fields
     *
     * @param string $name A case-insensitive header name.
     * @param string $headerLine The "header line" associated with the given 
     * header name as a comma-separated string of concatenated header values.
     * @return static
     */
    private function sendSingleHeader(string $name, string $headerLine): static {
        $responseHeader = sprintf('%s: %s', $name, $headerLine);

        header($responseHeader, false);

        return $this;
    }

    /**
     * Output the body of a HTTP response.
     * 
     * The response body will only be printed, if 
     * the response doesn't have a "Location" header.
     * 
     * @link https://tools.ietf.org/html/rfc7230#section-3.3 Hypertext Transfer Protocol 
     * (HTTP/1.1): Message Syntax and Routing > 3.3. Message Body
     * @link https://tools.ietf.org/html/rfc7231#section-7.1.2 Hypertext Transfer Protocol 
     * (HTTP/1.1): Semantics and Content > 7.1.2. Location
     * 
     * @param ResponseInterface $response A HTTP response.
     * @return static
     */
    private function outputBody(ResponseInterface $response): static {
        if (!$response->hasHeader('Location')) {
            echo $response->getBody();
        }

        return $this;
    }

}
