# Balances

## Usage With Eloquent Models
When you use the [`HasBalance`](./basic-usage.md#balances) trait in an Eloquent model, you can access the balance model using the `balance` method.
This method returns the balance model for the specified currency (or the default currency if not specified):
```php
/** @var \O21\LaravelWallet\Contracts\Balance $balance */
$balance = $user->balance('USD');
```
To get the balance value, use the `value` property, which returns a [`Numeric`](https://github.com/021-projects/numeric) object:
```php
$formatted = $balance->value->get(); // '100.00'
```
You also able to access the `value_on_hold` and `value_pending` properties to get the amount of funds on hold or pending:
```php
$onHold = $balance->value_on_hold->get(); // '0.00'
$pending = $balance->value_pending->get(); // '0.00'
```

::: tip
These properties are not tracked by default.
Check the [balance tracking](./configuration.md#balance-tracking) for more details.
:::

## State Logs

There are times when we want to remember the balance value when performing some operation – for example, to display it to the user in the transaction log.
To do this, use the `logState` method:
```php
$balance->logState();
```
You can pass a transaction as an argument to associate a created log with it and save the balance value before the transaction was performed:
```php
$balance->logState($tx);
```

::: tip
You can enable logging of balance states at the time of transaction execution with the [`balance.log_states`](./configuration.md#log-balance-states) config option.
:::

## Overview

### Getting Received Value
To get the total amount of funds received, use the `received` property:
```php
$received = $balance->received->get(); // '100.00'
```

### Getting Sent Value
To get the total amount of funds sent, use the `sent` property:
```php
$sent = $balance->sent->get(); // '0.00'
```

### Getting Transactions
To get all transactions associated with the balance, use the `transactions` property:
```php
$transactions = $balance->transactions;
```

### Recalculating
To recalculate the balance value from the `transactions` table, use the `recalculate` method:
```php
$balance->recalculate();
```

### Value Comparisons
Since the `value` property is a Numeric object, you can use all [comparative functions](https://github.com/021-projects/numeric?tab=readme-ov-file#comparisons):
```php
$balance->value->greaterThan('100.00'); // false
$balance->value->lessThanOrEqual('100.00'); // true
```

