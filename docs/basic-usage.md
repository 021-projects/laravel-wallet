# Basic Usage

## Balances
Any model that implements the interface [`O21\LaravelWallet\Contracts\Payable`](interfaces.md#payable) can have its own balance and execute transactions.
::: warning
Any changes to the balances must be made through transactions. Direct changes to the balance value will be lost after the next transaction is executed.
:::
##### Add Balances To User Model
```php
<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Models\Concerns\HasBalance;

class User extends Model implements Payable // [!code focus:4]
{
    use HasBalance;
}
```


## Transactions
::: tip
Transaction is transfer from sender to recipient.
Creating a transaction when the sender is absent or there are insufficient funds in his balance is only possible when `overcharge` mode is enabled.
:::

##### Transfer
```php
transfer(100, 'USD')->from($sender)->to($recipient)->commit();
```

##### Deposit
```php
deposit(100, 'USD')->to($recipient)->overcharge()->commit();
```

##### Charge
```php
charge(100, 'USD')->from($sender)->commit();
```
