<?php

declare(strict_types=1);

namespace App\Enum;

enum ViolationStatus
{
    case False;
    case True;
    case Irrelevant;
}
