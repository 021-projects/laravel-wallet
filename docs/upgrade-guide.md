# Upgrade Guide

## High Impact Changes
- [Balance Tracking](#balance-tracking)
- [Migration Helper Trait Removed](#migration-helper-trait-removed)
- [New Transaction Columns](#new-transaction-columns)
- [Numeric Class Extracted](#numeric-class-extracted)

## Medium Impact Changes
- [Conversation Processors Added](#conversation-processors-added)
- [Custodian Model Added](#custodian-model-added)
- [New Transaction Statuses](#new-transaction-statuses)

## Low Impact Changes
- [Balance Max Scale Removed](#balance-max-scale-removed)

## Config Changes

### Balance Tracking

**Likelihood Of Impact: High**

Laravel Wallet 9 combines the `balance.accounting_statuses` and `balance.extra_values` options into
one `balance.tracking`:

```php
'balance' => [
    'accounting_statuses' => [ // [!code --:8]
        \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
        \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
    ],
    'extra_values' => [
        'pending' => false,
        'on_hold' => false,
    ],
    'tracking' => [ // [!code ++:18]
        // The main value of the balance (aka confirmed/available)
        // Transactions with following statuses will be included in the recalculation
        'value' => [
            \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
            \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
        ],
        // The value of the balance that is pending
        // If empty, value will not be tracking
        'value_pending' => [
            // \O21\LaravelWallet\Enums\TransactionStatus::PENDING,
        ],
        // The value of the balance that is holding
        // If empty, value will not be tracking
        'value_on_hold' => [
            // \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
        ],
    ],
],
```

### Balance Max Scale Removed

**Likelihood Of Impact: Low**

The `balance.max_scale` option has been removed. The max scale of numeric values in package is now determined by
the `numeric.precise_scale` option.

```php
'balance' => [
    'max_scale' => 8, // [!code --]
],
'numeric' => [ // [!code ++:6]
    // The scale for a numbers in the operations with precise calculations required
    // (like division, multiplication, etc.)
    'precise_scale' => 22,
    'rounding_mode' => \Brick\Math\RoundingMode::DOWN,
],
```

### Conversation Processors Added

**Likelihood Of Impact: Medium**

```php
'processors' => [
    'deposit' => \O21\LaravelWallet\Transaction\Processors\DepositProcessor::class,
    'charge' => \O21\LaravelWallet\Transaction\Processors\ChargeProcessor::class,
    'conversion_credit' => \O21\LaravelWallet\Transaction\Processors\ConversionCreditProcessor::class, // [!code ++:2]
    'conversion_debit' => \O21\LaravelWallet\Transaction\Processors\ConversionDebitProcessor::class,
    'transfer' => \O21\LaravelWallet\Transaction\Processors\TransferProcessor::class,
],
```

### Custodian Model Added

**Likelihood Of Impact: Medium**

Change the configuration to include the `custodian` model:

```php
'models' => [
    'balance' => \O21\LaravelWallet\Models\Balance::class,
    'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
    'custodian' => \O21\LaravelWallet\Models\Custodian::class, // [!code ++]
    'transaction' => \O21\LaravelWallet\Models\Transaction::class,
],
'table_names' => [
    'balances' => 'balances',
    'balance_states' => 'balance_states',
    'custodians' => 'custodians', // [!code ++]
    'transactions' => 'transactions',
],
```

And then create a migration to add the `custodians` table:

```php
use function O21\LaravelWallet\ConfigHelpers\table_name;

public function up()
{
    Schema::create(table_name('custodians'), function (Blueprint $table) {
        $table->id();
        $table->uuid('name')->unique();
        $table->json('meta')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });
}

public function down()
{
    Schema::dropIfExists(table_name('custodians'));
}
```

## Migration Helper Trait Removed

**Likelihood Of Impact: High**

The package migrations used the trait `\O21\LaravelWallet\Concerns\MigrationHelper` which has been removed.
You need to remove this trait in already created migrations and replace it with the new syntax:
::: code-group

```php [8.x]
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \O21\LaravelWallet\Concerns\MigrationHelper; // [!code --]

    public function up()
    {
        Schema::create($this->assertTableName('balances'), function (Blueprint $table) { // [!code --]
            $table->id();
            $table->morphs('payable');
            $table->decimal('value', 16, 8)->default(0);
            $table->decimal('value_pending', 16, 8)->default(0);
            $table->decimal('value_on_hold', 16, 8)->default(0);
            $table->string('currency', 10)->index();
            $table->unique(['payable_id', 'payable_type', 'currency'], 'unique_balance');
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->assertTableName('balances')); // [!code --]
    }
};
```

```php [9.x]
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function O21\LaravelWallet\ConfigHelpers\table_name; // [!code ++]

return new class extends Migration
{
    public function up()
    {
        Schema::create(table_name('balances'), function (Blueprint $table) { // [!code ++]
            $table->id();
            $table->morphs('payable');
            $table->decimal('value', 16, 8)->default(0);
            $table->decimal('value_pending', 16, 8)->default(0);
            $table->decimal('value_on_hold', 16, 8)->default(0);
            $table->string('currency', 10)->index();
            $table->unique(['payable_id', 'payable_type', 'currency'], 'unique_balance');
        });
    }

    public function down()
    {
        Schema::dropIfExists(table_name('balances')); // [!code ++]
    }
};
```

:::

## New Transaction Columns

**Likelihood Of Impact: High**

The `transactions` table has new columns:

```php
Schema::create(table_name('transactions'), function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique(); // [!code ++]
    $table->nullableMorphs('from');
    $table->nullableMorphs('to');
    $table->decimal('amount', 16, 8)->unsigned()->default(0)->index();
    $table->decimal('commission', 16, 8)->unsigned()->default(0);
    $table->decimal('received', 16, 8)
        ->unsigned()
        ->default(0)
        ->comment('received = amount - commission')
        ->index();
    $table->enum('status', TransactionStatus::known())
            ->default(TransactionStatus::PENDING)
            ->index();
    $table->string('processor_id')->index();
    $table->json('meta')->nullable();
    $table->boolean('archived')->default(false)->index();
    $table->boolean('invisible')->default(false)->index(); // [!code ++:5]
    $table->integer('batch')
        ->unsigned()
        ->index()
        ->comment('batch id to track related transactions');
    $table->timestamp('created_at')->nullable()->index();
});
```

Create a migration to add these columns to the `transactions` table.

## New Transaction Statuses

**Likelihood Of Impact: Medium**

New transaction statuses have been added: `awaiting_approval`, `awaiting_payment`, `in_progress`.
You should add these statuses to allowed enum values for the `status` column in the `transactions` table.

```php
use O21\LaravelWallet\Enums\TransactionStatus;

Schema::table(table_name('transactions'), function (Blueprint $table) {
  $table->enum('status', TransactionStatus::known())->change();
});
```

## Numeric Class Extracted

**Likelihood Of Impact: High**

The `O21\LaravelWallet\Numeric` class has been extracted to the separate
package [021/numeric](https://github.com/021-projects/numeric) and renamed to `O21\Numeric\Numeric`.

```php
use O21\LaravelWallet\Numeric; // [!code --]
use O21\Numeric\Numeric; // [!code ++]
```
