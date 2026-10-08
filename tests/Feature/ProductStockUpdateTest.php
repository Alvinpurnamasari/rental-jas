<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Services\ActivityLogFileService;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductStockUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->instance(ActivityLogFileService::class, new class extends ActivityLogFileService {
            public function write(ActivityLog $log): void
            {
            }
        });
    }

    public function test_sales_updates_only_the_selected_size_and_sees_its_stock_context(): void
    {
        [$product, $branch] = $this->makeProductWithSizes([
            'S' => ['total' => 5, 'available' => 5],
            'M' => ['total' => 8, 'available' => 8],
            'L' => ['total' => 20, 'available' => 10],
            'XL' => ['total' => 3, 'available' => 3],
        ]);
        $sales = $this->makeUser($branch, User::ROLE_SALES);
        $large = $product->sizes()->where('size', 'L')->firstOrFail();
        $medium = $product->sizes()->where('size', 'M')->firstOrFail();

        $this->actingAs($sales)
            ->withSession(['_old_input' => ['size_id' => $large->id]])
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('name="size_id"', false)
            ->assertSee('data-size="L"', false)
            ->assertSee('Ukuran L')
            ->assertSee('Stok tersedia: 10 dari 20');

        $this->from(route('products.show', $product))
            ->patch(route('products.update-stock', $product), [
                'size_id' => $large->id,
                'stock_available' => 21,
            ])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('stock_available');
        $this->assertSame(10, (int) $product->sizes()->where('size', 'L')->value('stock_available'));
        $this->assertSame(0, ActivityLog::where('action', 'update_product_stock')->count());

        $this->from(route('products.show', $product))
            ->patch(route('products.update-stock', $product), [
                'size_id' => $large->id,
                'stock_available' => 15,
                'stock_note' => 'Penyesuaian stok ukuran L',
            ])
            ->assertRedirect(route('products.show', $product));

        $this->assertSame(5, (int) $product->sizes()->where('size', 'S')->value('stock_available'));
        $this->assertSame(8, (int) $product->sizes()->where('size', 'M')->value('stock_available'));
        $this->assertSame(15, (int) $product->sizes()->where('size', 'L')->value('stock_available'));
        $this->assertSame(3, (int) $product->sizes()->where('size', 'XL')->value('stock_available'));
        $this->assertSame(31, (int) $product->fresh()->stock_available);
        $this->assertSame(36, (int) $product->fresh()->stock_total);

        $this->from(route('products.show', $product))
            ->patch(route('products.update-stock', $product), [
                'size_id' => $medium->id,
                'stock_available' => 7,
            ])
            ->assertRedirect(route('products.show', $product));

        $this->assertSame(7, (int) $product->sizes()->where('size', 'M')->value('stock_available'));
        $this->assertSame(15, (int) $product->sizes()->where('size', 'L')->value('stock_available'));
        $this->assertSame(5, (int) $product->sizes()->where('size', 'S')->value('stock_available'));
        $this->assertSame(3, (int) $product->sizes()->where('size', 'XL')->value('stock_available'));
        $this->assertSame(30, (int) $product->fresh()->stock_available);

        $logs = ActivityLog::where('action', 'update_product_stock')->orderBy('id')->get();
        $largeLog = $logs->firstWhere('new_values.size_id', (int) $large->id);
        $this->assertNotNull($largeLog);
        $this->assertSame((int) $large->id, (int) $largeLog->old_values['size_id']);
        $this->assertSame('L', $largeLog->old_values['size']);
        $this->assertSame(10, (int) $largeLog->old_values['stock_available']);
        $this->assertSame(15, (int) $largeLog->new_values['stock_available']);
        $this->assertStringContainsString('Ukuran L', $largeLog->description);
    }

    public function test_single_size_product_stays_labeled_and_other_allowed_role_can_update_it(): void
    {
        [$product, $branch] = $this->makeProductWithSizes([
            'M' => ['total' => 8, 'available' => 8],
        ]);
        $size = $product->sizes()->firstOrFail();
        $sales = $this->makeUser($branch, User::ROLE_SALES);

        $this->actingAs($sales)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Ukuran M')
            ->assertSee('Stok tersedia: 8 dari 8')
            ->assertSee('name="size_id"', false);

        $admin = $this->makeUser($branch, User::ROLE_ADMIN_TOKO);
        $this->actingAs($admin)
            ->patch(route('products.update-stock', $product), [
                'size_id' => $size->id,
                'stock_available' => 0,
            ])
            ->assertRedirect();

        $this->assertSame(0, (int) $product->sizes()->whereKey($size->id)->value('stock_available'));
        $this->assertSame('rented', $product->fresh()->status);

        $this->patch(route('products.update-stock', $product), [
            'size_id' => $size->id,
            'stock_available' => 7,
        ])->assertRedirect();

        $this->assertSame(7, (int) $product->sizes()->whereKey($size->id)->value('stock_available'));
        $this->assertSame('available', $product->fresh()->status);
    }

    public function test_a_size_id_from_another_product_is_rejected_without_changing_stock(): void
    {
        [$product, $branch] = $this->makeProductWithSizes([
            'S' => ['total' => 5, 'available' => 5],
        ]);
        [$otherProduct] = $this->makeProductWithSizes([
            'L' => ['total' => 10, 'available' => 10],
        ]);
        $sales = $this->makeUser($branch, User::ROLE_SALES);

        $this->actingAs($sales)
            ->from(route('products.show', $product))
            ->patch(route('products.update-stock', $product), [
                'size_id' => $otherProduct->sizes()->value('id'),
                'stock_available' => 0,
            ])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('size_id');

        $this->assertSame(5, (int) $product->sizes()->where('size', 'S')->value('stock_available'));
        $this->assertSame(5, (int) $product->fresh()->stock_available);
        $this->assertSame(0, ActivityLog::where('action', 'update_product_stock')->count());
    }

    private function makeProductWithSizes(array $sizes): array
    {
        $branch = Branch::create([
            'name' => 'Cabang Test',
            'code' => 'T-' . Str::upper(Str::random(8)),
            'address' => 'Alamat test',
        ]);
        $category = Category::create([
            'name' => 'Formal ' . Str::random(6),
            'slug' => 'formal-' . Str::lower(Str::random(8)),
        ]);
        $product = Product::create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'code' => 'TEST-' . Str::upper(Str::random(12)),
            'name' => 'Jas Formal Hitam',
            'rental_price' => 100000,
            'deposit_price' => 0,
            'stock_total' => array_sum(array_column($sizes, 'total')),
            'stock_available' => array_sum(array_column($sizes, 'available')),
            'condition' => 'good',
            'status' => 'available',
        ]);

        foreach ($sizes as $size => $stock) {
            $product->sizes()->create([
                'size' => $size,
                'stock_total' => $stock['total'],
                'stock_available' => $stock['available'],
            ]);
        }

        return [$product, $branch];
    }

    private function makeUser(Branch $branch, string $role): User
    {
        return User::factory()->create([
            'name' => ucfirst($role) . ' Test',
            'role' => $role,
            'branch_id' => $branch->id,
            'branch_access_type' => 'specific',
        ]);
    }
}
