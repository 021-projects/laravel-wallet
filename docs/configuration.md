# Configuration
**Path to config**: `config/wallet.php`

## Balance Tracking
```php
return [
    // ...
    'balance' => [ // [!code focus:21]
        'tracking' => [
            // The main value of the balance (aka confirmed/available)
            // Transactions with following statuses will be included in the recalculation
            'value' => [
                \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
                \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
                \O21\LaravelWallet\Enums\TransactionStatus::IN_PROGRESS,
                \O21\LaravelWallet\Enums\TransactionStatus::AWAITING_APPROVAL,
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
        // ...
    ], // [!code focus]
];
```

### Track Custom Balances
::: warning
Replace `<status-name>` with the name of the status you want to track.
:::
1. Add a new field to the `balances` table:
::: details Migration
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function O21\LaravelWallet\ConfigHelpers\table_name;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(table_name('balances'), function (Blueprint $table) {
            $table->decimal('value_<status-name>', 16, 8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(table_name('balances'), function (Blueprint $table) {
            $table->dropColumn('value_<status-name>');
        });
    }
}
```
:::

2. Add the status to the `balance.tracking.value_<status-name>`:
```php
return [
    // ...
    'balance' => [
        'tracking' => [
            'value_<status-name>' => [ // [!code focus:3]
                '<status-name>' 
            ],
        ],
        // ...
    ],
];
```

## Default Currency
Laravel Wallet supports balances in multiple currencies, but also provides the ability to work conveniently when you only have one main currency.
This option allows you to specify the currency in which transactions and balances will be created, unless otherwise explicitly specified.

```php
return [
    'default_currency' => 'USD', // [!code focus]
    // ...
];
```

## Overriding Default Models

You can extend any model from the package:
```php
return [
    // ...
    'models' => [ // [!code focus:6]
        'balance'       => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'custodian'     => \O21\LaravelWallet\Models\Custodian::class,
        'transaction'   => \O21\LaravelWallet\Models\Transaction::class,
    ],
    // ...
];
```

:::: details Transaction Model Extending Example
::: code-group
```php [config/wallet.php]
return [
    // ...
    'models' => [
        'balance'       => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'custodian'     => \O21\LaravelWallet\Models\Custodian::class,
        'transaction'   => \App\Models\Transaction::class, // [!code focus]
    ],
    // ...
];
```
```php [app/Models/Transaction.php]
namespace App\Models;

use O21\LaravelWallet\Models\Transaction as BaseTransaction;

class Transaction extends BaseTransaction
{
    // ...
}
```
:::
::::

## Table Names
::: tip
If you want to change the table names, do it before running wallet migrations ;)
:::
By default, the package will use the following table names:

| Name             | Description                                   |
|------------------|-----------------------------------------------|
| `balances`       | Model balance values                          |
| `balance_states` | Snapshots of balance values for a transaction |
| `custodians`     | Anonymous / Model-free fund holders           |
| `transactions`   | Balance transactions                          |

It can be changed in the `table_names` section:
```php
return [
    // ...
    'table_names' => [ // [!code focus:5]
        'balances'       => 'balances',
        'balance_states' => 'balance_states',
        'custodians'     => 'custodians',
        'transactions'   => 'transactions',
    ],
    // ...
];
```

## Transaction Route Key
By default, the package uses the `uuid` field to identify transactions. If you want to use another field, you can specify it in the configuration:
```php
return [
    // ...
    'transactions' => [
        // ...
        'route_key' => 'id', // [!code focus]
        // ...
    ],
    // ...
];
```

## Scaling
By default, all numbers in the package are limited to `99,999,999.99999999`. This applies to balance values, transaction amounts, commissions, etc.
If you want to increase this limit, first of all you need to edit the migrations.

Let's increase the maximum values to `999,999,999.999999999999999999`:
::: code-group
```php [database/migrations/create_balances_table.php]
$table->decimal('value', 16, 8)->default(0); // [!code --:3]
$table->decimal('value_pending', 16, 8)->default(0);
$table->decimal('value_on_hold', 16, 8)->default(0);
$table->decimal('value', 27, 18)->default(0); // [!code ++:3]
$table->decimal('value_pending', 27, 18)->default(0);
$table->decimal('value_on_hold', 27, 18)->default(0);
```
```php [database/migrations/create_transactions_table.php]
$table->unsignedDecimal('amount', 16, 8)->default(0)->index(); // [!code --:6]
$table->unsignedDecimal('commission', 16, 8)->default(0);
$table->unsignedDecimal('received', 16, 8)
    ->default(0)
    ->comment('received = amount - commission')
    ->index();
$table->unsignedDecimal('amount', 27, 18)->default(0)->index(); // [!code ++:6]
$table->unsignedDecimal('commission', 27, 18)->default(0);
$table->unsignedDecimal('received', 27, 18)
    ->default(0)
    ->comment('received = amount - commission')
    ->index();
```
```php [database/migrations/create_balance_states_table.php]
$table->decimal('value', 16, 8)->default(0); // [!code --:3]
$table->decimal('value', 27, 18)->default(0); // [!code ++:3]
```
:::

### Currency Scaling
::: tip Note
Only affects the transaction table. Balance values still may have more decimals.
:::
With this option you can limit decimal places for transaction numbers (amount, commission, etc.) in certain currencies:
```php
return [
    // ...
    'transactions' => [
        // ...
        'currency_scaling' => [ // [!code focus:6]
            'USD' => 2,
            'EUR' => 2,
            'BTC' => 8,
            'ETH' => 8,
        ],
        // ...
    ],
];
```

## Log Balance States
You can enable logging of balance states at the time of transaction execution. 
```php
return [
    // ...
    'balance' => [
        // ...
        'log_states' => true, // [!code focus]
        // ...
    ], 
];
```
