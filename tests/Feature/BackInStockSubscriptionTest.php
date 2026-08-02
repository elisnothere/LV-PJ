<?php

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSourceMapping;
use App\Models\ProductStockSubscription;
use App\Models\User;
use App\Services\MailtrapBackInStockMailer;
use App\Services\ProductImportService;
use App\Services\StockSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

class FakeMailtrapBackInStockMailer extends MailtrapBackInStockMailer
{
    public array $sentTo = [];

    public function __construct(private array $failFor = [])
    {
    }

    public function sendBackInStockNotification(Product $product, string $recipientEmail): void
    {
        if (in_array($recipientEmail, $this->failFor, true)) {
            throw new RuntimeException('Mailtrap failed');
        }

        $this->sentTo[] = $recipientEmail;
    }
}

function stockCategory(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

function fakeMailtrapMailer(array $failFor = []): FakeMailtrapBackInStockMailer
{
    return new FakeMailtrapBackInStockMailer($failFor);
}

it('shows the stock subscription form on the public product detail page when stock is zero', function () {
    $product = Product::create([
        'name' => 'Tablet',
        'category_id' => stockCategory('Electronics')->id,
        'description' => 'Desc',
        'price' => 200,
        'stock' => 0,
        'active' => true,
    ]);

    $response = $this->get(route('catalog.show', $product));

    $response->assertOk();
    $response->assertSee('Avisame cuando vuelva a tener stock');
    $response->assertSee('Quiero aviso');
});

it('hides the stock subscription form when the product has stock', function () {
    $product = Product::create([
        'name' => 'Camara',
        'category_id' => stockCategory('Electronics')->id,
        'description' => 'Desc',
        'price' => 300,
        'stock' => 4,
        'active' => true,
    ]);

    $response = $this->get(route('catalog.show', $product));

    $response->assertOk();
    $response->assertDontSee('Avisame cuando vuelva a tener stock');
    $response->assertSee('Agregar al carrito');
});

it('creates a back in stock subscription for an out of stock product', function () {
    $product = Product::create([
        'name' => 'Auriculares',
        'category_id' => stockCategory('Audio')->id,
        'description' => 'Desc',
        'price' => 90,
        'stock' => 0,
        'active' => true,
    ]);

    $response = $this->post(route('product-stock-subscriptions.store', $product), [
        'email' => 'Cliente@Example.com',
    ]);

    $response->assertRedirect(route('catalog.show', $product));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('product_stock_subscriptions', [
        'product_id' => $product->id,
        'email' => 'cliente@example.com',
        'status' => 'pending',
    ]);
});

it('does not create duplicate pending subscriptions for the same product and email', function () {
    $product = Product::create([
        'name' => 'Mouse',
        'category_id' => stockCategory('Perifericos')->id,
        'description' => 'Desc',
        'price' => 50,
        'stock' => 0,
        'active' => true,
    ]);

    ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'cliente@example.com',
        'status' => 'pending',
    ]);

    $response = $this->post(route('product-stock-subscriptions.store', $product), [
        'email' => 'cliente@example.com',
    ]);

    $response->assertRedirect(route('catalog.show', $product));
    $response->assertSessionHas('success');
    expect(ProductStockSubscription::count())->toBe(1);
});

it('rejects stock subscriptions for products that already have stock', function () {
    $product = Product::create([
        'name' => 'Teclado',
        'category_id' => stockCategory('Perifericos')->id,
        'description' => 'Desc',
        'price' => 70,
        'stock' => 2,
        'active' => true,
    ]);

    $response = $this->post(route('product-stock-subscriptions.store', $product), [
        'email' => 'cliente@example.com',
    ]);

    $response->assertRedirect(route('catalog.show', $product));
    $response->assertSessionHas('error');
    expect(ProductStockSubscription::count())->toBe(0);
});

it('sends synchronous notifications through mailtrap when admin restocks a product from zero', function () {
    $mailer = fakeMailtrapMailer();
    app()->instance(MailtrapBackInStockMailer::class, $mailer);

    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin-restock@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $category = stockCategory('Gaming');
    $product = Product::create([
        'name' => 'Silla gamer',
        'category_id' => $category->id,
        'description' => 'Desc',
        'price' => 250,
        'stock' => 0,
        'active' => true,
    ]);

    ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'cliente@example.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->put(route('products.update', $product), [
        'name' => $product->name,
        'category_id' => $category->id,
        'description' => $product->description,
        'price' => $product->price,
        'stock' => 5,
        'active' => 1,
    ]);

    $response->assertRedirect(route('products.index'));

    expect($mailer->sentTo)->toBe(['cliente@example.com']);

    $subscription = ProductStockSubscription::first();
    expect($subscription->status)->toBe('notified')
        ->and($subscription->notified_at)->not->toBeNull();
});

it('does not resend notifications when a product already had stock before update', function () {
    $mailer = fakeMailtrapMailer();
    app()->instance(MailtrapBackInStockMailer::class, $mailer);

    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin-no-resend@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $category = stockCategory('Office');
    $product = Product::create([
        'name' => 'Escritorio',
        'category_id' => $category->id,
        'description' => 'Desc',
        'price' => 180,
        'stock' => 3,
        'active' => true,
    ]);

    ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'cliente@example.com',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->put(route('products.update', $product), [
        'name' => $product->name,
        'category_id' => $category->id,
        'description' => $product->description,
        'price' => $product->price,
        'stock' => 6,
        'active' => 1,
    ])->assertRedirect(route('products.index'));

    expect($mailer->sentTo)->toBe([])
        ->and(ProductStockSubscription::first()->status)->toBe('pending');
});

it('notifies pending subscribers when an importer restores stock', function () {
    $mailer = fakeMailtrapMailer();
    app()->instance(MailtrapBackInStockMailer::class, $mailer);
    Queue::fake();

    $category = stockCategory('Electronics');
    $product = Product::create([
        'name' => 'Consola',
        'category_id' => $category->id,
        'description' => 'Desc',
        'price' => 500,
        'stock' => 0,
        'active' => true,
        'canonical_key' => 'consola',
        'primary_source' => 'free_ecommerce',
    ]);

    ProductSourceMapping::create([
        'product_id' => $product->id,
        'source' => 'free_ecommerce',
        'external_id' => 'console-1',
        'checksum' => 'old',
        'raw_payload' => ['id' => 'console-1'],
    ]);

    ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'cliente@example.com',
        'status' => 'pending',
    ]);

    $adapter = new class implements ProductSourceAdapter
    {
        public function source(): string
        {
            return 'free_ecommerce';
        }

        public function fetchProducts(): iterable
        {
            return [[
                'id' => 'console-1',
                'name' => 'Consola',
                'description' => 'Desc',
                'category' => 'Electronics',
                'priceCents' => 50000,
                'stock' => 4,
                'image' => 'https://example.com/consola.jpg',
            ]];
        }

        public function normalize(array $product): NormalizedProductData
        {
            return new NormalizedProductData(
                source: 'free_ecommerce',
                externalId: (string) $product['id'],
                title: $product['name'],
                description: $product['description'],
                categoryName: $product['category'],
                priceAmount: $product['priceCents'] / 100,
                currency: 'USD',
                vendor: null,
                stock: (int) $product['stock'],
                imageUrls: [$product['image']],
                rawPayload: $product,
            );
        }
    };

    app(ProductImportService::class)->import($adapter);

    expect($mailer->sentTo)->toBe(['cliente@example.com'])
        ->and(ProductStockSubscription::first()->status)->toBe('notified');
});

it('continues processing other subscribers when one mailtrap email send fails', function () {
    $mailer = fakeMailtrapMailer(['fallo@example.com']);
    app()->instance(MailtrapBackInStockMailer::class, $mailer);

    $product = Product::create([
        'name' => 'Monitor',
        'category_id' => stockCategory('Displays')->id,
        'description' => 'Desc',
        'price' => 220,
        'stock' => 2,
        'active' => true,
    ]);

    $failedSubscription = ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'fallo@example.com',
        'status' => 'pending',
    ]);

    $successfulSubscription = ProductStockSubscription::create([
        'product_id' => $product->id,
        'email' => 'ok@example.com',
        'status' => 'pending',
    ]);

    $result = app(StockSubscriptionService::class)->notifyIfBackInStock($product, 0, 2);

    $failedSubscription->refresh();
    $successfulSubscription->refresh();

    expect($result['failed'])->toBe(1)
        ->and($result['notified'])->toBe(1)
        ->and($failedSubscription->status)->toBe('pending')
        ->and($successfulSubscription->status)->toBe('notified')
        ->and($mailer->sentTo)->toBe(['ok@example.com']);
});
