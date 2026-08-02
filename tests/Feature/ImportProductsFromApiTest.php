<?php

use App\Contracts\ProductSourceAdapter;
use App\Data\NormalizedProductData;
use App\Jobs\DownloadProductImageJob;
use App\Jobs\ImportEscuelaJsProductsJob;
use App\Jobs\ImportFreeEcommerceProductsJob;
use App\Jobs\ImportProductsFromApisJob;
use App\Jobs\ImportRouteMisrProductsJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSourceMapping;
use App\Services\ProductImportService;
use App\Services\ProductSources\RouteMisrProductsAdapter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function importTestCategory(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

function fakeAdapter(string $source, array $payloads, callable $normalizer): ProductSourceAdapter
{
    return new class($source, $payloads, $normalizer) implements ProductSourceAdapter
    {
        public function __construct(
            private string $sourceName,
            private array $payloads,
            private $normalizer,
        ) {
        }

        public function source(): string
        {
            return $this->sourceName;
        }

        public function fetchProducts(): iterable
        {
            return $this->payloads;
        }

        public function normalize(array $product): NormalizedProductData
        {
            return ($this->normalizer)($product, $this->sourceName);
        }
    };
}

function failingAdapter(string $source): ProductSourceAdapter
{
    return new class($source) implements ProductSourceAdapter
    {
        public function __construct(private string $sourceName)
        {
        }

        public function source(): string
        {
            return $this->sourceName;
        }

        public function fetchProducts(): iterable
        {
            throw new RuntimeException('Source unavailable');
        }

        public function normalize(array $product): NormalizedProductData
        {
            throw new LogicException('normalize should not be called');
        }
    };
}

it('imports free ecommerce products and queues local image downloads', function () {
    Queue::fake();

    $service = app(ProductImportService::class);

    $stats = $service->import(fakeAdapter('free_ecommerce', [[
        'id' => '1',
        'name' => 'Hydrating Facial Moisturizer',
        'description' => 'Daily hydration',
        'category' => 'Beauty',
        'priceCents' => 2000,
        'image' => 'https://example.com/moisturizer.jpg',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    }));

    expect($stats['created'])->toBe(1)
        ->and($stats['images_dispatched'])->toBe(1)
        ->and(Product::count())->toBe(1)
        ->and(ProductSourceMapping::count())->toBe(1)
        ->and(ProductImage::count())->toBe(1)
        ->and(Category::count())->toBe(1);

    $product = Product::with('category')->first();
    $image = ProductImage::first();

    expect($product->name)->toBe('Hydrating Facial Moisturizer')
        ->and($product->category?->name)->toBe('Beauty')
        ->and($product->primary_source)->toBe('free_ecommerce')
        ->and($image->external_url)->toBe('https://example.com/moisturizer.jpg')
        ->and($image->source)->toBe('imported_local');

    Queue::assertPushed(DownloadProductImageJob::class, 1);
});

it('imports route misr products with normalized category vendor and stock', function () {
    Queue::fake();

    $service = app(ProductImportService::class);

    $stats = $service->import(fakeAdapter('route_misr', [[
        'id' => 'rm-1',
        'title' => 'Route Headphones',
        'description' => 'Studio sound',
        'price' => 1499,
        'quantity' => 7,
        'imageCover' => 'https://example.com/cover.jpg',
        'images' => ['https://example.com/cover.jpg', 'https://example.com/alt.jpg'],
        'brand' => ['name' => 'Route'],
        'subcategory' => ['category' => ['name' => 'Electronics']],
        'updatedAt' => '2026-08-01T10:00:00.000Z',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['title'],
            description: $payload['description'],
            categoryName: $payload['subcategory']['category']['name'],
            priceAmount: (float) $payload['price'],
            currency: 'EGP',
            vendor: $payload['brand']['name'],
            stock: (int) $payload['quantity'],
            imageUrls: [$payload['imageCover'], ...$payload['images']],
            rawPayload: $payload,
            externalUpdatedAt: CarbonImmutable::parse($payload['updatedAt']),
        );
    }));

    $product = Product::with('category')->first();

    expect($stats['created'])->toBe(1)
        ->and(ProductImage::count())->toBe(2)
        ->and($product->vendor)->toBe('Route')
        ->and($product->category?->name)->toBe('Electronics')
        ->and($product->stock)->toBe(7)
        ->and($product->primary_source)->toBe('route_misr');
});

it('does not duplicate products mappings or categories on reimport', function () {
    Queue::fake();

    $service = app(ProductImportService::class);
    $adapter = fakeAdapter('free_ecommerce', [[
        'id' => '1',
        'name' => 'Hydrating Facial Moisturizer',
        'description' => 'Daily hydration',
        'category' => 'Beauty',
        'priceCents' => 2000,
        'image' => 'https://example.com/moisturizer.jpg',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    });

    $service->import($adapter);
    $stats = $service->import($adapter);

    expect(Product::count())->toBe(1)
        ->and(ProductSourceMapping::count())->toBe(1)
        ->and(ProductImage::count())->toBe(1)
        ->and(Category::count())->toBe(1)
        ->and($stats['unchanged'])->toBe(1);
});

it('reuses the same category entity for semantically equivalent imported names', function () {
    Queue::fake();

    $service = app(ProductImportService::class);

    $service->import(fakeAdapter('free_ecommerce', [[
        'id' => 'cat-1',
        'name' => 'Auriculares',
        'description' => 'Uno',
        'category' => 'Electronics & Gadgets',
        'priceCents' => 1000,
        'image' => 'https://example.com/a.jpg',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    }));

    $service->import(fakeAdapter('route_misr', [[
        'id' => 'cat-2',
        'title' => 'Camara',
        'description' => 'Dos',
        'price' => 1200,
        'quantity' => 2,
        'imageCover' => 'https://example.com/b.jpg',
        'images' => [],
        'brand' => ['name' => 'Route'],
        'subcategory' => ['category' => ['name' => ' electronics gadgets ']],
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['title'],
            description: $payload['description'],
            categoryName: $payload['subcategory']['category']['name'],
            priceAmount: (float) $payload['price'],
            currency: 'USD',
            vendor: $payload['brand']['name'],
            stock: (int) $payload['quantity'],
            imageUrls: [$payload['imageCover']],
            rawPayload: $payload,
        );
    }));

    expect(Category::count())->toBe(1);
});

it('never overwrites a manually created product with an imported one', function () {
    Queue::fake();

    $manualProduct = Product::create([
        'name' => 'Wireless Headphones',
        'category_id' => importTestCategory('Electronics')->id,
        'description' => 'Manual description',
        'price' => 79.99,
        'stock' => 2,
        'active' => true,
        'canonical_key' => 'wireless-headphones',
    ]);

    $service = app(ProductImportService::class);

    $stats = $service->import(fakeAdapter('escuelajs', [[
        'id' => '77',
        'title' => 'Wireless Headphones',
        'description' => 'Imported description',
        'category' => ['name' => 'Electronics'],
        'price' => 79.99,
        'images' => ['https://example.com/headphones.jpg'],
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['title'],
            description: $payload['description'],
            categoryName: $payload['category']['name'],
            priceAmount: (float) $payload['price'],
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: $payload['images'],
            rawPayload: $payload,
        );
    }));

    $manualProduct->refresh();

    expect($stats['created'])->toBe(1)
        ->and(Product::count())->toBe(2)
        ->and(ProductSourceMapping::count())->toBe(1)
        ->and($manualProduct->description)->toBe('Manual description')
        ->and($manualProduct->primary_source)->toBeNull();
});

it('updates an existing mapped product when the payload changes', function () {
    Queue::fake();

    $service = app(ProductImportService::class);

    $service->import(fakeAdapter('free_ecommerce', [[
        'id' => '5',
        'name' => 'Triple Blade Razor',
        'description' => 'Original description',
        'category' => 'Beauty',
        'priceCents' => 1000,
        'image' => 'https://example.com/razor.jpg',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    }));

    $stats = $service->import(fakeAdapter('free_ecommerce', [[
        'id' => '5',
        'name' => 'Triple Blade Razor',
        'description' => 'Updated description',
        'category' => 'Beauty',
        'priceCents' => 1250,
        'image' => 'https://example.com/razor.jpg',
    ]], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    }));

    $product = Product::with('category')->first();

    expect($stats['updated'])->toBe(1)
        ->and($product->description)->toBe('Updated description')
        ->and($product->category?->name)->toBe('Beauty')
        ->and((string) $product->price)->toBe('12.50');
});

it('keeps importing valid products when one product payload is invalid', function () {
    Queue::fake();

    $service = app(ProductImportService::class);

    $stats = $service->import(fakeAdapter('free_ecommerce', [
        [
            'id' => 'ok-1',
            'name' => 'Valid Product',
            'description' => 'Works',
            'category' => 'Beauty',
            'priceCents' => 2000,
            'image' => 'https://example.com/valid.jpg',
        ],
        [
            'id' => '',
            'name' => '',
            'description' => 'Broken',
            'category' => 'Beauty',
            'priceCents' => 1000,
            'image' => 'https://example.com/broken.jpg',
        ],
    ], function (array $payload, string $source) {
        return new NormalizedProductData(
            source: $source,
            externalId: (string) $payload['id'],
            title: (string) $payload['name'],
            description: $payload['description'],
            categoryName: $payload['category'],
            priceAmount: $payload['priceCents'] / 100,
            currency: 'USD',
            vendor: null,
            stock: 0,
            imageUrls: [$payload['image']],
            rawPayload: $payload,
        );
    }));

    expect($stats['fetched'])->toBe(2)
        ->and($stats['created'])->toBe(1)
        ->and($stats['failed'])->toBe(1)
        ->and(Product::count())->toBe(1)
        ->and(ProductSourceMapping::count())->toBe(1);
});

it('returns useful failure stats when a source cannot be fetched', function () {
    $service = app(ProductImportService::class);

    $stats = $service->import(failingAdapter('route_misr'));

    expect($stats['source'])->toBe('route_misr')
        ->and($stats['fetched'])->toBe(0)
        ->and($stats['failed'])->toBe(1)
        ->and(Product::count())->toBe(0);
});

it('retries route misr source fetches after a 429 response', function () {
    Http::fakeSequence()
        ->push(['message' => 'Too Many Requests'], 429, ['Retry-After' => '0'])
        ->push([
            'data' => [[
                'id' => 'rm-2',
                'title' => 'Recovered Product',
                'description' => 'Recovered',
                'price' => 50,
                'quantity' => 3,
                'imageCover' => 'https://example.com/recovered.jpg',
                'images' => [],
                'brand' => ['name' => 'Route'],
                'subcategory' => ['category' => ['name' => 'Electronics']],
            ]],
        ], 200);

    $adapter = new RouteMisrProductsAdapter();
    $products = $adapter->fetchProducts();

    expect($products)->toHaveCount(1)
        ->and($products[0]['id'])->toBe('rm-2');

    Http::assertSentCount(2);
});

it('downloads imported images locally without redownloading existing files', function () {
    Storage::fake('public');
    Http::fake([
        'https://i.imgur.com/R3iobJA.jpeg' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $product = Product::create([
        'name' => 'Cap',
        'category_id' => importTestCategory('Accessories')->id,
        'description' => 'Cap',
        'price' => 61,
        'stock' => 0,
        'active' => true,
        'canonical_key' => 'cap',
        'primary_source' => 'escuelajs',
    ]);

    $image = $product->images()->create([
        'image_url' => '',
        'external_url' => 'https://i.imgur.com/R3iobJA.jpeg',
        'source' => 'imported_local',
        'is_primary' => true,
        'sort_order' => 1,
    ]);

    $job = new DownloadProductImageJob($image->id, 'https://i.imgur.com/R3iobJA.jpeg');
    $job->handle();
    $job->handle();

    $image->refresh();
    $product->refresh();

    expect($image->image_url)->toStartWith('/storage/productos/importados/'.$product->id.'/')
        ->and($product->image_url)->toBe($image->image_url);

    Http::assertSentCount(1);
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $image->image_url));
});

it('seeds only the admin user and no demo products', function () {
    $this->seed();

    expect(Product::count())->toBe(0)
        ->and(\App\Models\User::query()->where('email', 'admin@example.com')->exists())->toBeTrue();
});

it('queues the orchestrator command', function () {
    Bus::fake();

    $this->artisan('products:import-api')
        ->expectsOutput('Import queued successfully.')
        ->assertSuccessful();

    Bus::assertDispatched(ImportProductsFromApisJob::class);
});

it('dispatches all source jobs from the orchestrator', function () {
    Bus::fake();

    (new ImportProductsFromApisJob())->handle();

    Bus::assertDispatched(ImportFreeEcommerceProductsJob::class);
    Bus::assertDispatched(ImportEscuelaJsProductsJob::class);
    Bus::assertDispatched(ImportRouteMisrProductsJob::class);
});
