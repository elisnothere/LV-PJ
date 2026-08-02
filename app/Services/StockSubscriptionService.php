<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStockSubscription;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockSubscriptionService
{
    public function __construct(private MailtrapBackInStockMailer $mailtrapMailer)
    {
    }

    public function subscribe(Product $product, string $email): array
    {
        if (! $product->active) {
            return [
                'created' => false,
                'duplicate' => false,
                'message' => 'El producto no esta disponible para suscripciones.',
            ];
        }

        if ($product->stock > 0) {
            return [
                'created' => false,
                'duplicate' => false,
                'message' => 'Este producto ya tiene stock disponible.',
            ];
        }

        $normalizedEmail = trim(strtolower($email));

        $subscription = ProductStockSubscription::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'email' => $normalizedEmail,
                'status' => 'pending',
            ],
            [
                'notified_at' => null,
            ],
        );

        if (! $subscription->wasRecentlyCreated) {
            return [
                'created' => false,
                'duplicate' => true,
                'message' => 'Ya registramos este correo para avisarte cuando vuelva el stock.',
            ];
        }

        return [
            'created' => true,
            'duplicate' => false,
            'message' => 'Te avisaremos por correo cuando este producto vuelva a tener stock.',
        ];
    }

    public function notifyIfBackInStock(Product $product, int $previousStock, int $newStock): array
    {
        if ($previousStock > 0 || $newStock < 1) {
            return [
                'notified' => 0,
                'failed' => 0,
                'skipped' => true,
            ];
        }

        $pendingSubscriptions = ProductStockSubscription::query()
            ->where('product_id', $product->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        $notified = 0;
        $failed = 0;

        foreach ($pendingSubscriptions as $subscription) {
            try {
                $this->mailtrapMailer->sendBackInStockNotification($product, $subscription->email);

                $subscription->forceFill([
                    'status' => 'notified',
                    'notified_at' => now(),
                ])->save();

                $notified++;
            } catch (Throwable $exception) {
                $failed++;

                Log::warning('Back in stock notification failed', [
                    'product_id' => $product->id,
                    'subscription_id' => $subscription->id,
                    'email' => $subscription->email,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'notified' => $notified,
            'failed' => $failed,
            'skipped' => false,
        ];
    }
}
