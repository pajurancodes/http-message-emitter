<?php

namespace PajuranCodes\Http\Message\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * An interface to an emitter of a HTTP response.
 *
 * @author pajurancodes
 */
interface ResponseEmitterInterface {

    /**
     * Emit a HTTP response and terminate the program.
     * 
     * This method sends the "status line" and 
     * the response headers first, then prints 
     * the response body on the screen.
     *
     * @param ResponseInterface $response A HTTP response.
     * @return never
     */
    public function emit(ResponseInterface $response): never;
}
