<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\View;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class MailtrapBackInStockMailer
{
    public function sendBackInStockNotification(Product $product, string $recipientEmail): void
    {
        $apiToken = (string) config('services.mailtrap.api_token');

        if (trim($apiToken) === '') {
            throw new \RuntimeException('Mailtrap API token is not configured.');
        }

        $mailtrap = MailtrapClient::initSendingEmails(
            apiKey: $apiToken,
            isBulk: (bool) config('services.mailtrap.bulk', true),
        );

        $detailUrl = route('catalog.show', $product);
        $html = View::make('mail.back-in-stock', [
            'product' => $product,
        ])->render();

        $priceText = $product->hasActivePromotion()
            ? '$' . number_format((float) $product->effective_price, 2) . ' (antes $' . number_format((float) $product->price, 2) . ')'
            : '$' . number_format((float) $product->price, 2);

        $text = implode(PHP_EOL, [
            'Tu producto ya esta disponible otra vez.',
            'Producto: ' . $product->name,
            'Categoria: ' . ($product->category?->name ?? 'Sin categoria'),
            'Precio: ' . $priceText,
            'Stock actual: ' . $product->stock,
            'Ver producto: ' . $detailUrl,
        ]);

        $email = (new MailtrapEmail())
            ->from(new Address(
                (string) config('services.mailtrap.from_address', 'hello@demomailtrap.co'),
                (string) config('services.mailtrap.from_name', config('app.name')),
            ))
            ->to(new Address($recipientEmail))
            ->subject('Tu producto ya esta disponible otra vez')
            ->category((string) config('services.mailtrap.category', 'Back in Stock'))
            ->html($html)
            ->text($text);

        $mailtrap->send($email);
    }
}
