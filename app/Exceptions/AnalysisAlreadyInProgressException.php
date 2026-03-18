<?php

namespace App\Exceptions;

use RuntimeException;

final class AnalysisAlreadyInProgressException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('解析を実行中です。完了までお待ちください。');
    }
}
