<?php

namespace App\Utilities;

use Illuminate\Support\Str;
use App\Models\User;

class ReferralHelper
{
   
    public static function generate(string $prefix = 'CLT'): string
    {
        do {
            $randomPart1 = strtoupper(Str::random(4));
            $randomPart2 = strtoupper(Str::random(4));
            $code = "{$prefix}-{$randomPart1}-{$randomPart2}";
        } while (self::codeExists($code));

        return $code;
    }

  
    protected static function codeExists(string $code): bool
    {
        return User::where('ref_code', $code)->exists();
    }
}
