<?php

namespace App\Exceptions;

use RuntimeException;

final class DifferentCompanyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('指定されたユーザーは選択できません。');
    }
}
