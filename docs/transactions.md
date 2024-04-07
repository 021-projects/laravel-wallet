# Transactions

## Creating
To get a transaction creator, use the `tx` function:
```php
$creator = tx(100, 'USD');
```

### Handling Errors
If you are performing an operation during which an unexpected exception might occur and you need to secure the means, put them in `before` or `after` hooks:
```php
// This code will roll back the transaction.
$creator->after(function () {
    throw new \Exception('Error');
});
```

### Invisible Transactions
If you need to create a transaction that will not be visible in the user's transaction history, use the `invisible` method:
```php
$creator->invisible();
```
Then you can filter out invisible transactions using the `skipInvisible` scope:
```php
$transactions = $user->balance()->transactions()->skipInvisible()->get();
```

### Transaction Meta
Use the `meta` method to add additional information to the transaction:
```php
$creator->meta(['key' => 'value']);
```

## Batching
Batching allow you to link multiple transactions. 
To set up a batch, use the `batch` method:
```php
$batchId = random_int(1, 1000);
$tx = $creator->batch($batchId, exists: false)->commit();
```
In case you try to add a new transaction to an already existing batch without explicitly specifying the `exists` parameter to `true`, you will receive an exception `\O21\LaravelWallet\Exception\ImplicitTxMergeAttemptException`.
### Getting Neighboring Transactions
To get transactions neighbours from the batch, use the `neighbours` property:
```php
/** @var \Illuminate\Database\Eloquent\Collection<\O21\LaravelWallet\Contracts\Transaction> $txs */
$tx->neighbours;
```

## Commission
Commission is a fee that is deducted from the amount received by the recipient.
This means that if you need to add a commission to the debit amount, you need to increase the transaction amount:

```php
$requestedAmountToWithdraw = 1;
$commission = 0.02; // 2%

$commissionValue = num($requestedAmountToWithdraw)->mul($commission); // [!code focus:13]
$amount = num($requestedAmountToWithdraw)->add($commissionValue);

transfer($amount)
    ->from($user)
    ->to($bank)
    ->commission($commission)
    ->commit();

// Result:
// user balance = initial balance - 1.02
// bank balance = initial balance + 1
```

### Commission Strategies
::: tip 
Strategy Enum: `O21\LaravelWallet\Enums\CommissionStrategy`
:::

You can use the following strategies to calculate the commission:

| Type    | Description                                                          | Example                                                                       |
|---------|----------------------------------------------------------------------|-------------------------------------------------------------------------------|
| Fixed   | Sets commission to fixed value                                       | `commission(0.02)`                                                            |
| Percent | Calculate commission based on transaction amount                     | `commission(2, strategy: CommissionStrategy::PERCENT)`                        |
| Mixed   | Calculate commission based on transaction amount and add fixed value | `commission(2, strategy: CommissionStrategy::PERCENT_AND_FIXED, fixed: 0.01)` |
::: warning
When using a strategy with calculations (for example, percent), after changing the transaction amount, the commission **will not be** recalculated automatically.
:::

### Minimum Commission
Pass the `minimum` parameter to set the minimum commission value:
```php
transfer(1)
    ->from($user)
    ->to($bank)
    ->commission(1, strategy: CommissionStrategy::PERCENT, minimum: 0.5)
    ->commit();
```

## Processors
Processors allow us to add additional logic to transactions: user interface, custom triggers, etc.
::: warning
Despite the fact that the processor interface allows you to implement business logic internally, and creator protects against leaks of funds during the processor code execution, to increase system stability it is recommended to minimize business logic in processors and move it to events.
:::

### Generate Processor

```bash
php artisan make:tx-processor WithdrawProcessor
```
::: details Result
```php
namespace App\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Concerns\BaseProcessor;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class WithdrawProcessor implements TransactionProcessor, InitialSuccess // [!code focus:4]
{
    use BaseProcessor;
}
```
:::

#### Add New Processor to Config

```php
'processors' => [
    // ...
    'withdraw' => \App\Transaction\Processors\WithdrawProcessor::class, // [!code focus]
],
```

### Useful Links
- [Batch Sync Trait](https://github.com/021-projects/laravel-wallet/blob/v9.x-dev/src/Transaction/Processors/Concerns/BatchSync.php) – allows you to synchronize transactions statuses with the batch.
- [Withdrawal Use Case](./best-practices.md#withdrawal-example)
