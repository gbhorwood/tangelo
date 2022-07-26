<?php
namespace Ghorwood\Tangelo\Exceptions;

class RouterException extends \Exception
{
    private Int $httpCode;

    public function __construct(String $message, Int $httpCode, Throwable $previous = null)
    {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
        $this->message = $message;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    public function __toString() {
        return "HTTP ".$this->httpCode." ".$this->message;
    }

}