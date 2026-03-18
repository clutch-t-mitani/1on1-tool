<?php

namespace App\Exceptions;

use RuntimeException;

final class DuplicateLogException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('本日分は既に入力済みです。');
    }
}
