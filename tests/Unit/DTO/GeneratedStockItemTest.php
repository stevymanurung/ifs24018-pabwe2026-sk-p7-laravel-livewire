<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedStockItem(
            name: 'Beras Premium',
            sku: 'BRS-001',
            description: 'Beras kualitas premium 5kg',
            quantity: 40,
            unit: 'karung',
            price: 75000.0,
            minStock: 10,
        );

        $this->assertEquals('Beras Premium', $dto->name);
        $this->assertEquals('BRS-001', $dto->sku);
        $this->assertEquals('Beras kualitas premium 5kg', $dto->description);
        $this->assertEquals(40, $dto->quantity);
        $this->assertEquals('karung', $dto->unit);
        $this->assertEquals(75000.0, $dto->price);
        $this->assertEquals(10, $dto->minStock);

        $this->assertEquals([
            'name' => 'Beras Premium',
            'sku' => 'BRS-001',
            'description' => 'Beras kualitas premium 5kg',
            'quantity' => 40,
            'unit' => 'karung',
            'price' => 75000.0,
            'min_stock' => 10,
        ], $dto->toArray());
    }

    public function test_dto_defaults(): void
    {
        $dto = new GeneratedStockItem(name: 'Minimal');

        $this->assertNull($dto->sku);
        $this->assertNull($dto->description);
        $this->assertSame(0, $dto->quantity);
        $this->assertSame('pcs', $dto->unit);
        $this->assertNull($dto->price);
        $this->assertSame(5, $dto->minStock);
    }
}
