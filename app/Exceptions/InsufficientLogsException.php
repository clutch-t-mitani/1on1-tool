<?php

namespace App\Exceptions;

use RuntimeException;

final class InsufficientLogsException extends RuntimeException
{
    public function __construct(int $remaining)
    {
        parent::__construct("あと{$remaining}日分の入力が必要です。");
    }
}
