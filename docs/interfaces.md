# Interfaces

## Numeric
- **Class:** `O21\LaravelWallet\Numeric`
- **Related Configuration:** [Scaling](configuration.md#scaling)

This class is used to work with numbers that are too large or too small for the `int` and `float` types.
Laravel Wallet uses this class for all calculations.
::: tip
Under hood class uses the [bcmath](https://www.php.net/manual/en/book.bc.php) module for calculations.
:::
::: tip
We also recommend using this class for calculations in **all** cases.
:::
```php
public function __construct(
    string|float|int|Numeric $value,
    // Number of decimal places to round to
    // If null, value will be taken from config('wallet.balance.max_scale')
    // With fallback to 8
    ?int $scale = null
);

public function __toString(): string;

// __toString() alias
public function get(): string;

// Absolute value, without "-"
public function positive(): string;

// Negative value, with "-"
public function negative(): string;

// Add value to current
public function add(string|float|int|Numeric $value): self;

// Subtract value from current
public function sub(string|float|int|Numeric $value): self;

// Multiply current value by value
public function mul(string|float|int|Numeric $value): self;

// Divide current value by value
public function div(string|float|int|Numeric $value): self;

// Check if current value is equal to specified
public function equals(string|float|int|Numeric $value): bool;

// Check if current value is greater than specified
public function greaterThan(string|float|int|Numeric $value): bool;

// Check if current value is greater than or equal to specified
public function greaterThanOrEqual(string|float|int|Numeric $value): bool;

// Check if current value is less than specified
public function lessThan(string|float|int|Numeric $value): bool;

// Check if current value is less than or equal to specified
public function lessThanOrEqual(string|float|int|Numeric $value): bool;

/**
 * Get the minimum value of the given values
 * Requires 021/laravel-wallet >= 8.2.0
 * 
 * @param  string|float|int|Numeric[]  ...$values
 * @return \O21\LaravelWallet\Numeric
 */
public function min(...$values): Numeric;

/**
 * Get the maximum value of the given values
 * Requires 021/laravel-wallet >= 8.2.0
 * 
 * @param  string|float|int|Numeric[]  ...$values
 * @return \O21\LaravelWallet\Numeric
 */
public function max(...$values): Numeric;

// Format value, uses PHP number_format() function
public function format(
    int $decimals = 8,
    string $decimalSeparator = '.',
    string $thousandsSeparator = '',
    mixed $value = null
): string;

// Set scale for current value
public function scale(int $scale): self;
```

## Balance
Model for storing balance values.
- **Default Model:** `O21\LaravelWallet\Models\Balance`
- **Contract Class:** `O21\LaravelWallet\Contracts\Balance`

```php
/** // [!code focus:10]
 * @property-read \O21\LaravelWallet\Numeric $sent
 * @property-read \O21\LaravelWallet\Numeric $received
 * @property-read \O21\LaravelWallet\Contracts\Payable $payable
 * @property string $currency
 */
 
// Recalculates the balance value based on the transactions
public function recalculate(): bool;

// Check is the balance value is equal to the given value
public function equals(string|float|int $value): bool;
// Check is the balance value is greater than the given value
public function greaterThan(string|float|int $value): bool;
// Check is the balance value is greater than or equal to the given value
public function greaterThanOrEqual(string|float|int $value): bool;
// Check is the balance value is less than the given value
public function lessThan(string|float|int $value): bool;
// Check is the balance value is less than or equal to the given value
public function lessThanOrEqual(string|float|int $value): bool;

public function payable(): MorphTo;
```

## Balance State
Model for storing balance state logs.
- **Default Model:** `O21\LaravelWallet\Models\BalanceState`
- **Contract Class:** `O21\LaravelWallet\Contracts\BalanceState`

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id // [!code focus:7]
 * @property int $balance_id
 * @property int|null $transaction_id
 * @property \O21\LaravelWallet\Numeric $value
 * @property \Carbon\Carbon $created_at
 * @property-read \O21\LaravelWallet\Contracts\Balance $balance
 * @property-read \O21\LaravelWallet\Contracts\Transaction|null $tx
 */
 
public function payable(): MorphTo;
public function tx(): BelongsTo;
```

## Payable
Model which can have a balance and make transactions.
- **Default Model:** `O21\LaravelWallet\Models\Payable`
- **Contract Class:** `O21\LaravelWallet\Contracts\Payable`

::: tip
Trait `O21\LaravelWallet\Models\Concerns\HasBalance` implements this interface, so you can use it in any existing model.
:::

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function balance(?string $currency = null): Balance; // [!code focus:4]
public function balanceStates(): MorphMany;
public function assertHaveFunds(string $needs, ?string $currency = null): void;
public function isEnoughFunds(string $needs, ?string $currency = null): bool;
public function getMorphClass();
public function getKey();
```

## Transaction
Transaction model.
- **Default Model:** `O21\LaravelWallet\Models\Transaction`
- **Contract Class:** `O21\LaravelWallet\Contracts\Transaction`

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** // [!code focus:21] 
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $from
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $to
 * @property-read ?\O21\LaravelWallet\Contracts\TransactionProcessor $processor
 */

public function toApi(): array;

public function hasStatus(string $status): bool;
public function updateStatus(string $status): bool;

public function getMeta(string $key = null, $default = null);
public function setMeta(
    array|string $key,
    float|array|int|string $value = null
): void;
public function updateMeta(
    array|string $key,
    float|array|int|string $value = null
): bool;

/**
 * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\O21\LaravelWallet\Contracts\Payable>
 */
public function from(): MorphTo;

/**
 * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\O21\LaravelWallet\Contracts\Payable>
 */
public function to(): MorphTo;

/**
 * @return \Illuminate\Database\Eloquent\Casts\Attribute<\O21\LaravelWallet\Contracts\TransactionProcessor>
 */
public function processor(): Attribute;
```

## Transaction Creator
A class that provides safe creation of transactions.
- **Default Class:** `O21\LaravelWallet\Transaction\Creator`
- **Contract Class:** `O21\LaravelWallet\Contracts\TransactionCreator`

::: warning
Replacing the instance of this interface is not recommended.
:::
::: tip
Exceptions thrown in the `before` and `after` callbacks will be roll back the transaction and all database changes made in the callbacks.
:::
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Numeric;

public function commit(): Transaction; // [!code focus:12]
public function before(callable $before): self;
public function after(callable $after): self;
public function amount(string|float|int|Numeric $amount): self;
public function currency(string $currency): self;
public function commission(string|float|int|Numeric $commission): self;
public function status(string $status): self;
public function setDefaultStatus(): self;
public function processor(string $processor): self;
public function from(Payable $payable): self;
public function to(Payable $payable): self;
public function meta(array $meta): self;
public function lockOnRecord(Model|Builder|bool $lockRecord): self;
```

## Transaction Processor
- **Contract Class:** `O21\LaravelWallet\Contracts\TransactionProcessor`

::: tip
Despite the fact that the processor interface allows you to implement business logic internally, and creator protects against leaks of funds during the processor code execution, to increase system stability it is recommended to minimize business logic in processors.
:::
```php
/**
 * @method void statusChanged(string $status, string $oldStatus) Called in jobs by TransactionStatusChanged event
 * @method void creating() Called before transaction is created
 * @method void created() Called in jobs by TransactionCreated event
 * @method void updating() Called before transaction is updated
 * @method void updated() Called in jobs by TransactionUpdated event
 * @method void deleting() Called before transaction is deleted
 * @method void deleted() Called in jobs by TransactionDeleted event
 *
 */
public function __construct(Transaction $transaction);

/**
 * Method for preparing metadata
 *
 * @param  array  $meta
 * @return array
 */
public function prepareMeta(array $meta): array;
```

## Transaction Preparer
A global preparer for transactions.
::: warning
Default preparer sets `received` field to `amount - commission`.
If you plan to extend Preparer, it should also implement this behavior.
:::
- **Default Class:** `O21\LaravelWallet\Transaction\Preparer`
- **Contract Class:** `O21\LaravelWallet\Contracts\TransactionPreparer`

```php
public function prepare(Transaction $tx): void;
```
