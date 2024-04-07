<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use O21\Numeric\Numeric;

class MinimumNum implements ValidationRule
{
    protected Numeric $min;

    public function __construct($min = '0.00000001')
    {
        $this->min = num($min);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (num($value)->lessThan($this->min)) {
            $fail("The {$attribute} must be greater than {$this->min}.");
        }
    }
}
