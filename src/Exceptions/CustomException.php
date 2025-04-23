<?php

namespace Apiato\Core\Exceptions;

use Exception;

class CustomException extends Exception
{
    protected int $customCode = 400;

    protected array $customMessages = [];

    public function render($request)
    {
        return response()->json($this->customMessages, $this->customCode);
    }

    public function setCustomCode(int $code): static
    {
        $this->customCode = $code;

        return $this;
    }

    public function setCustomMessages(array $messages): static
    {
        $this->customMessages = $messages;

        return $this;
    }
}
