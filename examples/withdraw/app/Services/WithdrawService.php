<?php

// FundsAPI is a abstraction for external API

namespace App\Services;

use FundsAPI\Exceptions\BadRequestException;
use FundsAPI\Payout;
use Illuminate\Http\Exceptions\HttpResponseException;
use O21\LaravelWallet\Contracts\Transaction;

class WithdrawService
{
    public function createPayout(
        string $amount,
        string $destination,
        Transaction $tx
    ): Payout {
        try {
            $payout = FundsAPI::createPayout(
                $amount,
                $destination,
                $tx->currency,
                meta: [
                    'txid' => $tx->id,
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

        $tx->updateMeta($this->payoutMeta($payout, $destination));

        return $payout;
    }

    protected function payoutMeta(Payout $payout, string $destination): array
    {
        return [
            'payout'  => [
                'id' => $payout->getId(),
            ],
            'comment' => [
                'type'  => 'text',
                'value' => $destination,
            ]
        ];
    }
}
