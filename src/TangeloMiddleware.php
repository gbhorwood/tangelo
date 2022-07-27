<?php
namespace Ghorwood\Tangelo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Ghorwood\Tangelo\ConfigLookup as ConfigLookup;


/**
 * Superclass for user-created middleware.
 */
abstract class TangeloMiddleware
{

    /**
     * Configuration lookup table wrapper
     */
    protected ConfigLookup $config;

    /**
     * Super constructor for user middleware, injects ConfigLookup
     *
     * @param  ConfigLookup $config
     */
    public function __construct(ConfigLookup $config) {
        $this->config = $config;
    }

}