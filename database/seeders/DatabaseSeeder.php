<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        $items = [
            ['name' => 'Beras Premium 5kg', 'sku' => 'BRS-001', 'description' => 'Beras kualitas premium kemasan **5 kg**.', 'quantity' => 40, 'unit' => 'karung', 'price' => 75000, 'min_stock' => 10],
            ['name' => 'Minyak Goreng 2L', 'sku' => 'MYK-002', 'description' => 'Minyak goreng kemasan pouch 2 liter.', 'quantity' => 4, 'unit' => 'pcs', 'price' => 38000, 'min_stock' => 5],
            ['name' => 'Gula Pasir 1kg', 'sku' => 'GLA-003', 'description' => 'Stok habis, menunggu pengiriman supplier.', 'quantity' => 0, 'unit' => 'kg', 'price' => 17000, 'min_stock' => 5],
        ];

        foreach ($items as $item) {
            Stock::firstOrCreate(
                ['user_id' => $member->id, 'sku' => $item['sku']],
                $item + ['status' => StockStatus::fromQuantity($item['quantity'], $item['min_stock'])->value]
            );
        }
    }
}
