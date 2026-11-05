<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockExcelService(new StockRepository());
    }

    public function test_build_spreadsheet_and_export_response(): void
    {
        $user = User::factory()->create();
        $stocks = Stock::factory()->count(2)->create(['user_id' => $user->id]);

        $spreadsheet = $this->service->buildSpreadsheet($stocks, 'Custom Stocks');
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals('Custom Stocks', $spreadsheet->getActiveSheet()->getTitle());

        $response = $this->service->exportToResponse($stocks, 'test.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $outputContent = ob_get_clean();
        $this->assertNotEmpty($outputContent);
    }

    public function test_import_spreadsheet(): void
    {
        $user = User::factory()->create();

        // Create an in-memory spreadsheet and save to a temporary file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        foreach (['No', 'Name', 'SKU', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status', 'Description'] as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).'1', $header);
        }

        // Row 2: Standard stock with explicit status
        foreach (['1', 'Imported Stock 1', 'IMP-1', '30', 'box', '12500', '10', 'available', 'Desc 1'] as $i => $value) {
            $sheet->setCellValue(chr(65 + $i).'2', $value);
        }

        // Row 3: invalid status falls back to derived status, '-' values become null, invalid numbers use defaults
        foreach (['2', 'Imported Stock 2', '-', 'abc', '', 'n/a', 'x', 'unknown_status', '-'] as $i => $value) {
            $sheet->setCellValue(chr(65 + $i).'3', $value);
        }

        // Row 4: low quantity, status derived automatically from quantity vs min stock
        foreach (['3', 'Imported Stock 3', 'IMP-3', '3', 'kg', '900', '5', '', 'Low desc'] as $i => $value) {
            $sheet->setCellValue(chr(65 + $i).'4', $value);
        }

        // Row 5: stray header row (ignored)
        $sheet->setCellValue('A5', 'No');
        $sheet->setCellValue('B5', 'Name');
        $sheet->setCellValue('C5', 'Skip this');

        // Row 6: empty name (ignored)
        $sheet->setCellValue('A6', '4');
        $sheet->setCellValue('C6', 'NO-NAME');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertEquals(3, $result['imported']);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Imported Stock 1',
            'sku' => 'IMP-1',
            'quantity' => 30,
            'unit' => 'box',
            'min_stock' => 10,
            'status' => 'available',
            'description' => 'Desc 1',
        ]);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Imported Stock 2',
            'sku' => null,
            'description' => null,
            'quantity' => 0,
            'unit' => 'pcs',
            'price' => null,
            'min_stock' => 5,
            'status' => 'out_of_stock',
        ]);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Imported Stock 3',
            'quantity' => 3,
            'status' => 'low_stock',
        ]);
        $this->assertDatabaseMissing('stocks', ['sku' => 'NO-NAME']);
    }

    public function test_exported_spreadsheet_contains_stock_columns(): void
    {
        $user = User::factory()->create();
        $withPrice = Stock::factory()->create(['user_id' => $user->id, 'name' => 'Berharga', 'sku' => 'PRC-1', 'price' => 2500, 'quantity' => 4]);
        $noPrice = Stock::factory()->create(['user_id' => $user->id, 'name' => 'Tanpa Harga', 'sku' => null, 'price' => null, 'description' => null]);

        $spreadsheet = $this->service->buildSpreadsheet(Stock::query()->orderBy('id')->get());
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Name', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Min Stock', $sheet->getCell('G1')->getValue());
        $this->assertEquals('Berharga', $sheet->getCell('B2')->getValue());
        $this->assertEquals('PRC-1', $sheet->getCell('C2')->getValue());
        $this->assertEquals(4, $sheet->getCell('D2')->getValue());
        $this->assertEquals(2500, $sheet->getCell('F2')->getValue());
        $this->assertEquals('-', $sheet->getCell('C3')->getValue());
        $this->assertEquals('-', $sheet->getCell('F3')->getValue());
        $this->assertEquals('-', $sheet->getCell('I3')->getValue());
    }
}
