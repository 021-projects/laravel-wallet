# Helpers

## `num` 
Creates a new [`O21\LaravelWallet\Numeric`](interfaces.md#numeric) instance from a value.
```php
function num(string|float|int|Numeric $value): Numeric
// 8.1+
function num(string|float|int|Numeric $value, ?int $scale = null): Numeric // [!code ++]
```

## `tx`
Resolves a [O21\LaravelWallet\Contracts\TransactionCreator](interfaces.md#transaction-creator) instance and sets the amount and currency if specified.
```php
function tx(
    string|float|int|Numeric|null $amount = null,
    ?string $currency = null
): TransactionCreator;
```

## `deposit`
Call the `tx` function and sets the `deposit` processor.
```php
function deposit(
    string|float|int|Numeric|null $amount = null,
    ?string $currency = null
): TransactionCreator;
```

## `charge`
Call the `tx` function and sets the `charge` processor.
```php
function charge(
    string|float|int|Numeric|null $amount = null,
    ?string $currency = null
): TransactionCreator;
```

## `transfer`
Call the `tx` function and sets the `transfer` processor.
```php
function transfer(
    string|float|int|Numeric|null $amount = null,
    ?string $currency = null
): TransactionCreator;
```
