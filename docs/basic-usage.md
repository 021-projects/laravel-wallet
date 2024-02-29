# Basic Usage

## Balances
Any Eloquent model that implements the [`Payable`](./interfaces.md#payable) interface can have own balance in multiple currencies.
To quickly implement this interface in a model, use the `HasBalance` trait:

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

## Transactions

### Deposit
```php
deposit(100, 'USD')->to($recipient)->overcharge()->commit();
```

### Conversion
```php
conversion(1, 'USD')->to('EUR')->at(0.92)->performOn($payable);
```

::: tip
For conversions at rates with a decimal fraction greater than the value set in wallet.balance.max_scale, pass a Numeric object with an explicit scale parameter:
```php
conversion(20, 'KZT')
    ->to('BTC')
    ->at(num(1, scale: 15)->div(500_120_962.21)) // 1 KZT = 0.000000001999516 BTC
    ->performOn($payable);
```
:::

### Charge
```php
charge(100, 'USD')->from($sender)->commit();
```

### Transfer
```php
transfer(100, 'USD')->from($sender)->to($recipient)->commit();
```

### Overcharge Mode
A transaction represents a funds transfer from a sender to a recipient.
If the sender is missing or has insufficient funds, you will receive an exception when creating the transaction. 
To prevent this, you need to enable the `overcharge` mode:
```php
transfer(100, 'USD')->from($sender)->to($recipient)->overcharge()->commit();
```
Use it if you want to allow negative balances or creating a deposit.

Check the [deep dive](./transactions.md) section for more details.

## Custodians
Sometimes we need to have a balance for an abstraction that doesn't have its own model.
In this case, we can use the `Custodian` model:

```php
deposit(100, 'USD')->to(custodian('subservice_name'))->commit();

// or

use O21\LaravelWallet\Models\Custodian;
deposit(100, 'USD')->to(Custodian::of('subservice_name'))->commit();
```

You can also create anonymous custodians:

```php
custodian();

// or 

use O21\LaravelWallet\Models\Custodian;
Custodian::of();
```
