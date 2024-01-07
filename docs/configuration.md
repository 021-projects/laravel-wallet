# Configuration
**Path to config**: `config/wallet.php`

## Accounting Transaction Statuses
The balance value is `funds_received - funds_sent`<br>
Where `funds_received` is
```sql
SUM(transactions.received) WHERE transactions.status IN (<accounting-statuses>)
```
And `funds_sent` is
```sql 
SUM(transactions.amount) WHERE transactions.status IN (<accounting-statuses>)
```
```php
<?php

return [
    // ...
    'balance' => [ // [!code focus]
        // ...
        'accounting_statuses' => [ // [!code focus:4]
            \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
            \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD
        ],
        // ...
    ],
];
```

## Default Currency
Laravel Wallet supports balances in multiple currencies, but also provides the ability to work conveniently when you only have one main currency.
This option allows you to specify the currency in which transactions and balances will be created, unless otherwise explicitly specified.

```php
<?php

return [
    'default_currency' => 'USD', // [!code focus]
    // ...
];
```

## Overriding Default Models

You can extend any model from the package:
```php
<?php

return [
    // ...
    'models' => [ // [!code focus:5]
        'balance'       => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'transaction'   => \O21\LaravelWallet\Models\Transaction::class,
    ],
    // ...
];
```

:::: details Transaction Model Extending Example
::: code-group
```php [config/wallet.php]
<?php

return [
    // ...
    'models' => [
        'balance'       => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'transaction'   => \App\Models\Transaction::class, // [!code focus]
    ],
    // ...
];
```
```php [app/Models/Transaction.php]
<?php

namespace App\Models;

use O21\LaravelWallet\Models\Transaction as BaseTransaction;

class Transaction extends BaseTransaction
{
    public function toApi(): array
    {
        $output = parent::toApi();
        $output['from'] = $this->from?->toApi();
        $output['to'] = $this->to?->toApi();
        return $output;
    }
}
```
:::
::::

## Table Names
::: tip
If you want to change the table names, do it before running wallet migrations ;)
:::
By default, the package will use the following table names:

| Name             | Description                    |
|------------------|--------------------------------|
| `balances`       | For storing balances           |
| `balance_states` | For storing balance state logs |
| `transactions`   | For storing transactions       |

It can be changed in the `table_names` section:
```php
<?php

return [
    // ...
    'table_names' => [ // [!code focus:5]
        'balances'       => 'balances',
        'balance_states' => 'balance_states',
        'transactions'   => 'transactions',
    ],
    // ...
];
```

## Tracking Different Balance States
You may also need to track balance state for transactions with other statuses. By default, you can enable balance tracking for transactions with the status `on_hold` and `pending`.
After enabling these options, the `value_pending` and `value_on_hold` fields will be saved in the `balances` table.
```php
<?php

return [
    // ...
    'balance' => [ // [!code focus:5]
        'extra_values' => [
            'pending' => true,
            'on_hold' => true,
        ],
        // ...
    ], // [!code focus]
];
```

You can also track transactions of other statuses. 
To do this, add a new field to the `balances` table:
::: warning
Replace `<status-name>` with the name of the status you want to track.
:::
::: details Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \O21\LaravelWallet\Concerns\MigrationHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->assertTableName('balances'), function (Blueprint $table) {
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
        Schema::table($this->assertTableName('balances'), function (Blueprint $table) {
            $table->dropColumn('value_<status-name>');
        });
    }
}
```
:::

And just add the status to the `balance.extra_values`:
```php

return [
    // ...
    'balance' => [
        'extra_values' => [
            // enable value_pending calculation
            'pending' => true,
            // enable value_on_hold calculation
            'on_hold' => true,
            '<status-name>' => true, // [!code focus]
        ],
        // ...
    ],
];
```

## Log Balance States
You can enable logging of balance states at the time of transaction execution. 
```php
<?php

return [
    // ...
    'balance' => [ // [!code focus]
        // ...
        'log_states' => true, // [!code focus]
        // ...
    ], // [!code focus]
];
```
