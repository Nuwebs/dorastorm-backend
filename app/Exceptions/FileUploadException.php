<?php
namespace App\Exceptions;

use Exception;

class FileUploadException extends Exception
{
    protected string $message;
    protected int $code;
    protected Exception $previous;

    public function __construct(
        string $message = 'The file could not be uploaded',
        int $code = 500,
        Exception $prev = null
    ) {
        parent::__construct($message, $code, $prev);
        $this->message = $message;
        $this->code = $code;
        $this->previous = $prev;
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
