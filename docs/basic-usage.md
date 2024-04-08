# Basic Usage

## Balances
Any Eloquent model that implements the `Payable` interface can have own balance in multiple currencies.
To quickly implement this interface in a model, use the `HasBalance` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\Payable; // [!code focus:7]
use O21\LaravelWallet\Models\Concerns\HasBalance;

class User extends Model implements Payable
{
    use HasBalance;
}
```

### Useful Links
- [The Basics: Balances](./balances.md)

## Transactions

### Deposit
```php
deposit(100, 'USD')->to($recipient)->overcharge()->commit();
```

### Conversion
```php
conversion(1, 'USD')->to('EUR')->at(0.92)->performOn($payable);
```

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
::: tip
Use it if you want to allow negative balances or creating a deposit.
:::

### Useful Links
- [The Basics: Transactions](./transactions.md)

## Custodians
Sometimes we need to have a balance for an abstraction that doesn't have its own model.
In this case, we can use the `Custodian` model:

```php
deposit(100, 'USD')->to(custodian('subservice_name'))->commit();
```
or
```php
use O21\LaravelWallet\Models\Custodian;
deposit(100, 'USD')->to(Custodian::of('subservice_name'))->commit();
```

#### Getting Anonymous Custodian
```php
$temp = custodian();
```
or 
```php
use O21\LaravelWallet\Models\Custodian;
Custodian::of();
```
