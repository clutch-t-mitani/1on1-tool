<?php

namespace App\Exceptions;

use RuntimeException;

final class AnalysisAlreadyPublishedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('公開済みのため変更できません。');
    }
}
