<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletWithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use O21\LaravelWallet\Enums\CommissionStrategy;
use O21\LaravelWallet\Enums\TransactionStatus;
use Illuminate\Routing\Controller;

class WalletController extends Controller
{
    public function withdraw(
        WalletWithdrawRequest $request,
        WithdrawService $service
    ): JsonResponse {
        $amount = $request->get('amount');

        $fee = $this->getWithdrawFee();

        $tx = tx($amount)
            ->commission(
                $fee['percent'],
                strategy: CommissionStrategy::PERCENT_AND_FIXED,
                fixed: $fee['fixed'], // fixed amount
                minimum: $fee['minimum'] // minimum commission
            )
            ->processor('withdraw')
            ->from($request->user())
            ->status(TransactionStatus::AWAITING_APPROVAL)
            ->after(
                /**
                 * Creating payout in after() closure allows you to avoid
                 * the situation when the transaction is created, but the payout is not.
                 */
                function ($tx) use ($request, $service) {
                    $service->createPayout(
                        $request->get('amount'),
                        $request->get('destination'),
                        $tx
                    );
                }
            )->commit();

        return response()->json($tx->toApi());
    }

    protected function getWithdrawFee(): array
    {
        return [
            'percent' => '2',
            'fixed' => '0',
            'min' => '0.0001',
        ];
    }
}
