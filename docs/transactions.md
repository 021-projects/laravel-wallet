# Transactions

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

## Processing
[Processors](interfaces.md#transaction-processor) are used to add additional logic to transactions. For example, you can add an additional withdrawal processor to display the transaction on the user side accordingly.

#### Generate Processor
```bash
php artisan make:tx-processor WithdrawProcessor
```
##### Add Generated Processor to Config
```php
'processors' => [
    // ...
    'withdraw' => \App\Transaction\Processors\WithdrawProcessor::class,
],
```

::: details Generated Processor
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

<div class="tip custom-block" style="padding-top: 8px">

Check the [best practices](best-practices.md) section for more examples.

</div>
