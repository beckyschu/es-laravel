<?php

namespace App\Exceptions;

use Illuminate\Support\MessageBag;

class ValidationException extends \Exception
{
    protected $errors;

    public function __construct(MessageBag $errors)
    {
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getResponse()
    {
        $errors = [];

        foreach ($this->errors->all() as $error) {
            array_push($errors, [
                'status' => 422,
                'title'  => 'Unprocessable Entity',
                'detail' => $error
            ]);
        }

        return ['errors' => $errors];
    }
}
