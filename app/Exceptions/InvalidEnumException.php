<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ResponseHandler;

class InvalidEnumException extends Exception
{
    use ResponseHandler;

    public $field;
    public $value;

    public function __construct($field, $value, $model, $message = null)
    {
        // parent::__construct($field, $value);
        $this->field = $field;
        $this->value = $value;
        $this->model = $model;
        $this->message = $message;
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return $this->response("04", "invalid data send", "invalid value for " .$this->model."::$this->field ($this->value)", 422);
    }
}
