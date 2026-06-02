<?php

namespace App\Exceptions;

use App\Supports\Facades\Response\Response;
use Exception;

class BusinessException extends Exception
{
    protected $errorCode;

    protected $statusCode;

    public function __construct(string $errorCode, string $message = '', int $statusCode = 400)
    {
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode);
    }

    public function render($request)
    {
        return Response::failure(
            [
                'message' => $this->getMessage(),
                'error_code' => $this->errorCode,
            ],
            $this->statusCode
        );
    }
}
