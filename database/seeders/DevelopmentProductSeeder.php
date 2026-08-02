<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DevelopmentProductSeeder extends Seeder
{
    private const CATALOG = [
        'Electronics' => [
            'Wireless Noise-Cancelling Headphones',
            'Smartwatch Active Series',
            'Portable Bluetooth Speaker',
            'Mechanical Gaming Keyboard',
            'Ergonomic Wireless Mouse',
            '4K Action Camera',
            '10-Inch Android Tablet',
            '27-Inch QHD Monitor',
            'WiFi 6 Dual-Band Router',
            'USB-C Docking Station',
        ],
        'Home & Kitchen' => [
            'Digital Air Fryer 5L',
            'Programmable Coffee Maker',
            'Stainless Steel Cookware Set',
            'High-Power Blender',
            'Robot Vacuum Cleaner',
            'Memory Foam Pillow Pair',
            'Electric Kettle 1.7L',
            'Bamboo Cutting Board Set',
            'Compact Microwave Oven',
            'Cotton Bed Sheet Set',
        ],
        'Sports & Fitness' => [
            'Adjustable Dumbbell Set 20kg',
            'Non-Slip Yoga Mat',
            'Indoor Cycling Bike',
            'Resistance Band Training Kit',
            'Insulated Sports Bottle',
            'Running Shoes Pro',
            'Fitness Tracker Band',
            'Foldable Weight Bench',
            'Training Football',
            'Hiking Backpack 35L',
        ],
        'Fashion' => [
            'Classic Denim Jacket',
            'Slim Fit Chino Pants',
            'Everyday Leather Sneakers',
            'Water-Resistant Windbreaker',
            'Canvas Crossbody Bag',
            'Premium Cotton T-Shirt',
            'Polarized Sunglasses',
            'Leather Bifold Wallet',
            'Knitted Cardigan',
            'Urban Travel Backpack',
        ],
        'Beauty & Care' => [
            'Vitamin C Facial Serum',
            'Hydrating Daily Moisturizer',
            'Professional Hair Dryer',
            'Ceramic Hair Straightener',
            'Natural Skin Care Kit',
            'Electric Beard Trimmer',
            'Floral Eau de Parfum',
            'Mineral Sunscreen SPF 50',
            'Makeup Brush Collection',
            'Repairing Hair Mask',
        ],
        'Books & Stationery' => [
            'Productivity Planner 2026',
            'Watercolor Pencil Set',
            'Hardcover Dotted Notebook',
            'Modern Business Handbook',
            'Children Adventure Collection',
            'Calligraphy Starter Kit',
            'Desk Organizer Set',
            'World Atlas Illustrated',
            'Creative Writing Workbook',
            'Technical Drawing Set',
        ],
        'Toys & Games' => [
            'Wooden Building Blocks',
            'Strategy Board Game',
            'Remote Control Racing Car',
            'Educational Science Kit',
            'Family Puzzle 1000 Pieces',
            'Interactive Plush Toy',
            'Magnetic Construction Set',
            'Classic Chess Set',
            'Kids Art Activity Box',
            'Outdoor Bubble Machine',
        ],
        'Automotive' => [
            'Portable Tire Inflator',
            'Car Phone Mount',
            'Emergency Roadside Kit',
            'Waterproof Car Cover',
            'Dual USB Car Charger',
            'Microfiber Cleaning Set',
            'HD Dashboard Camera',
            'Leather Seat Organizer',
            'Digital Tire Gauge',
            'LED Headlight Bulb Pair',
        ],
        'Pet Supplies' => [
            'Orthopedic Pet Bed',
            'Automatic Pet Feeder',
            'Adjustable Dog Harness',
            'Cat Scratching Tower',
            'Stainless Pet Bowl Set',
            'Interactive Treat Toy',
            'Pet Grooming Brush',
            'Portable Travel Carrier',
            'Washable Training Pads',
            'Reflective Walking Leash',
        ],
        'Garden & Outdoor' => [
            'Compact Garden Tool Set',
            'Solar Patio Light Pack',
            'Expandable Garden Hose',
            'Outdoor Folding Chair',
            'Ceramic Plant Pot Set',
            'Portable Charcoal Grill',
            'Watering Can 5L',
            'Weatherproof Picnic Blanket',
            'Pruning Shears Pro',
            'Raised Garden Bed Kit',
        ],
    ];

    private const VENDORS = [
        'Nova Market',
        'Atlas Goods',
        'Urban Works',
        'Terra Living',
        'Pixel House',
        'Prime Select',
        'Blue Harbor',
        'Evergreen Co.',
    ];

    private const COLORS = [
        ['#0f766e', '#99f6e4'],
        ['#1d4ed8', '#bfdbfe'],
        ['#b45309', '#fde68a'],
        ['#be123c', '#fecdd3'],
        ['#4338ca', '#c7d2fe'],
        ['#15803d', '#bbf7d0'],
    ];

    public function run(): void
    {
        $number = 0;
        $seededAt = now();
        $seededCanonicalKeys = [];

        foreach (self::CATALOG as $categoryName => $productNames) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Category::makeSlug($categoryName)],
                ['name' => $categoryName],
            );

            foreach ($productNames as $itemIndex => $productName) {
                $number++;
                $price = $this->priceFor($number, $itemIndex);
                $promotion = $this->promotionFor($number, $price, $seededAt);
                $canonicalKey = sprintf(
                    'dev-seed-%03d-%s',
                    $number,
                    Product::makeCanonicalKey($productName),
                );
                $seededCanonicalKeys[] = $canonicalKey;

                $product = DB::transaction(function () use (
                    $category,
                    $productName,
                    $canonicalKey,
                    $number,
                    $price,
                    $promotion,
                ): Product {
                    return Product::query()->updateOrCreate(
                        ['canonical_key' => $canonicalKey],
                        [
                            'name' => $productName,
                            'category_id' => $category->id,
                            'description' => sprintf(
                                '%s by %s, selected for dependable everyday use and backed by practical features.',
                                $productName,
                                self::VENDORS[$number % count(self::VENDORS)],
                            ),
                            'price' => $price,
                            ...$promotion,
                            'stock' => $this->stockFor($number),
                            'image_url' => null,
                            'active' => $number % 20 !== 0,
                            'primary_source' => null,
                            'vendor' => self::VENDORS[$number % count(self::VENDORS)],
                        ],
                    );
                });

                $this->seedImages($product, $number);
            }
        }

        Product::query()
            ->where('canonical_key', 'like', 'dev-seed-%')
            ->whereNotIn('canonical_key', $seededCanonicalKeys)
            ->delete();
    }

    private function priceFor(int $number, int $itemIndex): float
    {
        $categoryTier = intdiv($number - 1, 10);
        $base = [120, 45, 35, 28, 18, 12, 20, 24, 16, 22][$categoryTier];

        return round($base + ($itemIndex * 8.75) + (($number * 13) % 29) + 0.90, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionFor(int $number, float $price, Carbon $seededAt): array
    {
        $scenario = $number % 10;
        $promotionalPrice = round($price * (0.68 + (($number % 3) * 0.06)), 2);

        return match ($scenario) {
            0 => [
                'promotional_price' => $promotionalPrice,
                'promotional_starts_at' => null,
                'promotional_ends_at' => null,
            ],
            1 => [
                'promotional_price' => $promotionalPrice,
                'promotional_starts_at' => $seededAt->copy()->subDays(10),
                'promotional_ends_at' => $seededAt->copy()->addDays(20),
            ],
            2 => [
                'promotional_price' => $promotionalPrice,
                'promotional_starts_at' => $seededAt->copy()->subDays(5),
                'promotional_ends_at' => null,
            ],
            3 => [
                'promotional_price' => $promotionalPrice,
                'promotional_starts_at' => $seededAt->copy()->addDays(7),
                'promotional_ends_at' => $seededAt->copy()->addDays(30),
            ],
            4 => [
                'promotional_price' => $promotionalPrice,
                'promotional_starts_at' => $seededAt->copy()->subDays(45),
                'promotional_ends_at' => $seededAt->copy()->subDays(5),
            ],
            default => [
                'promotional_price' => null,
                'promotional_starts_at' => null,
                'promotional_ends_at' => null,
            ],
        };
    }

    private function stockFor(int $number): int
    {
        if ($number % 7 === 0) {
            return 0;
        }

        if ($number % 9 === 0) {
            return 1 + ($number % 3);
        }

        return 5 + (($number * 7) % 46);
    }

    private function seedImages(Product $product, int $number): void
    {
        $product->images()->delete();

        if ($number % 8 === 0) {
            $product->forceFill(['image_url' => null])->save();

            return;
        }

        $imageCount = 1 + ($number % 3);
        $primaryUrl = null;

        for ($position = 1; $position <= $imageCount; $position++) {
            $path = sprintf(
                'productos/seeded/%s/view-%d.svg',
                $product->canonical_key,
                $position,
            );
            $url = Storage::disk('public')->url($path);

            Storage::disk('public')->put(
                $path,
                $this->svgFor($product->name, $number, $position),
            );

            $product->images()->create([
                'image_url' => $url,
                'source' => 'seed',
                'is_primary' => $position === 1,
                'sort_order' => $position,
            ]);

            $primaryUrl ??= $url;
        }

        $product->forceFill(['image_url' => $primaryUrl])->save();
    }

    private function svgFor(string $productName, int $number, int $position): string
    {
        [$background, $accent] = self::COLORS[($number + $position) % count(self::COLORS)];
        $safeName = htmlspecialchars($productName, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $label = htmlspecialchars(sprintf('View %d', $position), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="960" height="720" viewBox="0 0 960 720" role="img" aria-label="{$safeName}">
  <rect width="960" height="720" fill="{$background}"/>
  <circle cx="770" cy="120" r="210" fill="{$accent}" opacity=".30"/>
  <circle cx="150" cy="650" r="260" fill="{$accent}" opacity=".18"/>
  <rect x="145" y="135" width="670" height="410" rx="48" fill="#ffffff" opacity=".94"/>
  <path d="M390 260h180l70 70-160 145-160-145z" fill="{$accent}"/>
  <circle cx="480" cy="340" r="56" fill="{$background}" opacity=".82"/>
  <text x="480" y="600" text-anchor="middle" font-family="Verdana, sans-serif" font-size="34" font-weight="700" fill="#ffffff">{$safeName}</text>
  <text x="480" y="650" text-anchor="middle" font-family="Verdana, sans-serif" font-size="22" fill="#ffffff" opacity=".85">{$label}</text>
</svg>
SVG;
    }
}
