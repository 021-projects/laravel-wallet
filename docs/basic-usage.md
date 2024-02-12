# Basic Usage

## Balances
::: warning
To ensure the integrity of balance data, all modifications must be performed via transactions. Direct adjustments to balance values are not persistent and will be overwritten upon the execution of any subsequent transaction.
:::
Models that implement the [`Payable`](./interfaces.md#payable) interface are capable of maintaining individual balances, alongside the transactions associated with them.

##### Integrating balances into model
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Models\Concerns\HasBalance;

class User extends Model implements Payable // [!code focus:4]
{
    use HasBalance;
}
```

## Conducting Transactions
::: tip
A transaction represents a monetary transfer from a sender to a recipient. Transactions can only be created if the sender has adequate funds, except when the `overcharge` mode is activated, allowing for transactions beyond the available balance.
:::

### Overcharge Mode
When the `overcharge` mode is enabled, transactions can be conducted even without the sender or if the sender has insufficient funds.
Use it if you want to allow negative balances or creating a deposit.

##### Performing a Transfer
```php
transfer(100, 'USD')->from($sender)->to($recipient)->commit();
```

##### Making a Deposit
```php
deposit(100, 'USD')->to($recipient)->overcharge()->commit();
```

##### Charge
```php
charge(100, 'USD')->from($sender)->commit();
```
