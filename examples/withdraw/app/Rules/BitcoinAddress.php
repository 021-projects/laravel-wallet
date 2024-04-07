<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Kielabokkie\Bitcoin\AddressValidator;

class BitcoinAddress implements ValidationRule
{
    protected AddressValidator $validator;

    public function __construct()
    {
        $this->validator = new AddressValidator();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->validator->isValid($value)) {
            $fail("The $attribute must be a valid bitcoin address.");
        }
    }
}
