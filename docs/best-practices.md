# Best Practices

## Withdrawal Example

### Controller
::: code-group
```php [Controllers/WalletController.php]
// FundsAPI is a abstraction for external API

namespace App\Http\Controllers;

use App\Http\Requests\WalletWithdrawRequest;
use FundsAPI\Exceptions\BadRequestException;
use FundsAPI\Payout;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use O21\LaravelWallet\Enums\CommissionStrategy;

class WalletController extends Controller
{
    public function withdraw(WalletWithdrawRequest $request): JsonResponse // [!code focus:40]
    {
        $amount = $request->get('amount');
        $destination = $request->get('destination');

        $fee = $this->getWithdrawFee();

        $tx = tx($amount)
            ->commission(
                $fee['percent'],
                strategy: CommissionStrategy::PERCENT_AND_FIXED,
                fixed: $fee['fixed'], // fixed amount
                minimum: $fee['minimum'] // minimum commission
            )
            ->processor('withdraw')
            ->from(auth()->user())
            ->status('awaiting_approval')
            ->after(
                /**
                 * Creating payout in after() closure allows you to avoid
                 * the situation when the transaction is created, but the payout is not.
                 */
                function (Transaction $tx)
                use ($destination, $store) {
                    $payout = $this->createPayout(
                        $tx->received, // received = amount - commission
                        $destination,
                        $tx
                    );

                    $tx->updateMeta([
                        'payout' => [
                            'id'      => $payout->getId(),
                        ],
                        'comment' => [
                            'type'  => 'text',
                            'value' => $destination,
                        ]
                    ]);
                }
            )->commit();

        return response()->json($tx->toApi());
    }

    protected function createPayout(
        string $amount,
        string $destination,
        Transaction $tx
    ): Payout {
        try {
            $payout = FundsAPI::createPayout(
                $amount,
                $destination,
                $tx->getCurrency(),
                meta: [
                    'txid' => $tx->getId(),
                ]
            );
        } catch (BadRequestException $e) {
            $response = @json_decode(
                \Str::extractJson($e->getMessage()),
                true,
                512,
                JSON_THROW_ON_ERROR
            ) ?? [];

            $code = $response['code'] ?? $e->getCode();
            $message = $response['message'] ?? $e->getMessage();

            throw new HttpResponseException(
                response()->json([
                    'errors' => [
                        $code => [
                            $message,
                        ],
                    ],
                ], 422)
            );
        }

        return $payout;
    }
}
```
```php [Requests/WalletWithdrawRequest.php]
namespace App\Http\Requests\V1;

use App\Rules\BitcoinAddress;
use App\Rules\MinimumNum;
use Illuminate\Foundation\Http\FormRequest;

class WalletWithdrawRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array // [!code focus:8]
    {
        $user = $this->user();
        return [
            'amount'      => ['required', new MinimumNum, 'lte:'.$user->balance()->value],
            'destination' => ['required', new BitcoinAddress],
        ];
    }
}
```
:::

### Rules
::: code-group
```php [Rules/MinimumNum.php]
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use O21\LaravelWallet\Numeric;

class MinimumNum implements ValidationRule // [!code focus:21]
{
    protected Numeric $min;

    public function __construct($min = '0.00000001')
    {
        $this->min = num($min);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (num($value)->lessThan($this->min)) {
            $fail("The {$attribute} must be greater than {$this->min}.");
        }
    }
}
```
```php [Rules/BitcoinAddress.php]
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Kielabokkie\Bitcoin\AddressValidator;

class BitcoinAddress implements ValidationRule // [!code focus:21]
{
    protected AddressValidator $validator;

    public function __construct()
    {
        $this->validator = new AddressValidator();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->validator->isValid($value)) {
            $fail("The $attribute must be a valid bitcoin address.");
        }
    }
}
```
:::

### Transaction Processor
::: code-group
```php [Transaction/Processors/WithdrawProcessor.php]
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
