<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use App\Models\RentalItem;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
    
        $query = Product::with(['category', 'branches', 'sizes'])
            ->when(
                !$user->isSuperAdmin() && $user->branch_access_type === 'specific',
                function ($q) use ($user) {
                    $q->whereHas('branches', function ($bq) use ($user) {
                        $bq->whereIn('branches.id', $user->accessible_branch_ids);
                    });
                }
            )
            ->when($request->category, fn ($q) => $q->where('category_id', $request->category))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when(
                $request->size,
                fn ($q) => $q->whereHas('sizes', fn ($sq) => $sq->where('size', $request->size))
            )
            ->when(
                $request->branch_id,
                fn ($q) => $q->whereHas('branches', fn ($bq) => $bq->where('branches.id', $request->branch_id))
            )
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                        ->orWhere('code', 'like', "%{$request->search}%")
                        ->orWhere('color', 'like', "%{$request->search}%");
                });
            })
            ->latest();
    
        $products = $query->paginate(16)->withQueryString();
    
        $categories = Category::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
    
        $availableSizes = ProductSize::select('size')
            ->distinct()
            ->orderByRaw("FIELD(size,'XS','S','M','L','XL','XXL','3XL','4XL','5XL','6XL','7XL')")
            ->pluck('size')
            ->toArray();
        
        $stats = [
            'total' => (clone $query)->count(),

            'available' => (clone $query)
                ->where('status', 'available')
                ->count(),

            'rented_units' => (clone $query)
                ->where('status', 'rented')
                ->sum(\DB::raw('stock_total - stock_available')),

            'maintenance' => RentalItem::query()
                ->where('maintenance_status', 'pending')
                ->whereHas('product', function ($q) use ($user) {
                    $q->when(
                        !$user->isSuperAdmin()
                        && $user->branch_access_type === 'specific',
                        function ($branchQuery) use ($user) {
                            $branchQuery->whereHas('branches', function ($bq) use ($user) {
                                $bq->whereIn(
                                    'branches.id',
                                    $user->accessible_branch_ids
                                );
                            });
                        }
                    );
                })
                ->count(),
        ];
    
        return view('products.index', compact(
            'products',
            'categories',
            'branches',
            'stats',
            'availableSizes'
        ));
    }
    

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $nextCode = $this->generateCode();
        return view('products.create', compact('categories', 'branches', 'nextCode'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:150',
            'description'   => 'nullable|string',
            'color'         => 'nullable|string|max:50',
            'brand'         => 'nullable|string|max:100',
            'rental_price'  => 'required|numeric|min:0',
            'deposit_price' => 'nullable|numeric|min:0',
            'condition'     => 'required|in:excellent,good,fair,poor',
            'status'        => 'required|in:available,maintenance,inactive',
            'notes'         => 'nullable|string',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'branch_ids'    => 'nullable|array',
            'branch_ids.*'  => 'exists:branches,id',
            'sizes'         => 'required|array|min:1',
            'sizes.*.stock_total'    => 'required|integer|min:0',
            'sizes.*.stock_available' => 'required|integer|min:0',
        ]);

        foreach ($data['sizes'] as $sizeName => $stock) {
            if ($stock['stock_available'] > $stock['stock_total']) {
                return back()->withErrors([
                    "sizes.{$sizeName}.stock_available" => "Stok tersedia tidak boleh melebihi stok total untuk ukuran {$sizeName}.",
                ])->withInput();
            }
        }

        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $branchIds = $request->filled('branch_ids') ? $request->input('branch_ids') : [$user->branch_id ?? 1];
            $legacyBranchId = $branchIds[0];
        } else {
            $branchIds = [$user->branch_id];
            $legacyBranchId = $user->branch_id;
        }

        $data['branch_id']       = $legacyBranchId;
        $data['size']            = null;
        $data['stock_total']     = array_sum(array_column($data['sizes'], 'stock_total'));
        $data['stock_available'] = array_sum(array_column($data['sizes'], 'stock_available'));
        $sizesData               = $this->formatSizesData($data);
        $data['code']            = $this->generateCode();

        if ($request->hasFile('image')) {
            $data['photo'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image'], $data['branch_ids'], $data['sizes']);

        $product = Product::create($data);
        $product->branches()->sync($branchIds);
        $product->sizes()->createMany($sizesData);

        $this->generateQrCode($product);

        return redirect()->route('products.show', $product)
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdminToko())) {
            abort(403, 'Unauthorized action.');
        }
          $this->ensureBranchAccess($product);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:100',
            'rental_price' => 'required|numeric|min:0',
            'deposit_price' => 'nullable|numeric|min:0',
            'condition' => 'required|in:excellent,good,fair,poor',
            'status' => 'sometimes|in:available,maintenance,inactive',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'sizes' => 'sometimes|array|min:1',
            'sizes.*.stock_total'    => 'required|integer|min:0',
            'sizes.*.stock_available' => 'required|integer|min:0',
        ]);

        foreach (($data['sizes'] ?? []) as $sizeName => $stock) {
            if ($stock['stock_available'] > $stock['stock_total']) {
                return back()->withErrors([
                    "sizes.{$sizeName}.stock_available" => "Stok tersedia tidak boleh melebihi stok total untuk ukuran {$sizeName}.",
                ])->withInput();
            }
        }

        $requestedBranchIds = null;
        if ($request->filled('branch_ids')) {
            $requestedBranchIds = array_map('intval', $data['branch_ids']);

            if (!$user->isSuperAdmin()) {
                $allowedBranchIds = $user->accessible_branch_ids;
                $existingBranchIds = $product->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
                if ($existingBranchIds === [] && $product->branch_id) {
                    $existingBranchIds = [(int) $product->branch_id];
                }

                abort_unless(array_diff($requestedBranchIds, array_merge($allowedBranchIds, $existingBranchIds)) === [], 403);
                $requestedBranchIds = array_values(array_unique(array_merge(
                    array_diff($existingBranchIds, $allowedBranchIds),
                    array_intersect($requestedBranchIds, $allowedBranchIds)
                )));
            }
        }

        if ($request->hasFile('image')) {
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            $data['photo'] = $request->file('image')->store('products', 'public');
        } elseif ($request->input('remove_photo') === '1') {
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            $data['photo'] = null;
        } else {
            $data['photo'] = $product->photo;
        }

        unset($data['image'], $data['branch_ids']);

        if (array_key_exists('sizes', $data)) {
            $sizesData = $this->formatSizesData($data);
            $data['stock_total']     = array_sum(array_column($sizesData, 'stock_total'));
            $data['stock_available'] = array_sum(array_column($sizesData, 'stock_available'));
            $data['size']            = null;
            unset($data['sizes']);
        }

        $product->update($data);

        if ($request->filled('sizes')) {
            $product->sizes()->delete();
            $product->sizes()->createMany($sizesData);
        }

        if ($requestedBranchIds !== null) {
            $product->branches()->sync($requestedBranchIds);
        }

        return redirect()->route('products.show', $product)->with('success', 'Produk berhasil diperbarui!');
    }

    public function show(Product $product)
    {
        $user = Auth::user();
    
        if (!$user || (
            !$user->isSuperAdmin() &&
            !$user->isAdminToko() &&
            !$user->isSales()
        )) {
            abort(403, 'Unauthorized action.');
        }
    
        $this->ensureBranchAccess($product);
    
        // Data produk dan riwayat penyewaan
        $product->load([
            'sizes',
            'category',
            'branch',
            'rentalItems' => fn ($q) => $q
                ->with('rental.customer')
                ->latest()
                ->limit(10),
        ]);
    
        // Barang yang masih menunggu maintenance,
        // khusus untuk produk yang sedang dibuka
        $maintenanceItems = RentalItem::query()
            ->where('product_id', $product->id)
            ->where('maintenance_status', 'pending')
            ->with(['rental.customer'])
            ->latest()
            ->get();
    
        return view('products.show', compact(
            'product',
            'maintenanceItems'
        ));
    }
    

    public function updateStock(Request $request, Product $product)
    {
        $this->ensureBranchAccess($product);
        $validated = $request->validate([
            'size_id' => [
                'required',
                'integer',
                Rule::exists('product_sizes', 'id')->where(fn($query) => $query->where('product_id', $product->id)),
            ],
            'stock_available' => ['required', 'integer', 'min:0'],
            'stock_note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($request, $product, $validated) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $productSize = ProductSize::query()
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->find($validated['size_id']);

            if (!$productSize) {
                throw ValidationException::withMessages([
                    'size_id' => 'Ukuran yang dipilih tidak tersedia untuk produk ini.',
                ]);
            }

            $newStock = (int) $validated['stock_available'];
            if ($newStock > (int) $productSize->stock_total) {
                throw ValidationException::withMessages([
                    'stock_available' => "Stok tersedia ukuran {$productSize->size} tidak boleh melebihi stok total ({$productSize->stock_total}).",
                ]);
            }

            $oldStock = (int) $productSize->stock_available;
            if ($newStock !== $oldStock) {
                $lockedProduct->setSizeStockAvailable($productSize, $newStock);
            }

            $note = $validated['stock_note'] ?? null;
            $oldValues = [
                'size_id' => (int) $productSize->id,
                'size' => $productSize->size,
                'stock_available' => $oldStock,
            ];
            $newValues = [
                'size_id' => (int) $productSize->id,
                'size' => $productSize->size,
                'stock_available' => $newStock,
                'stock_note' => $note,
            ];

            $description = 'Ukuran ' . $productSize->size . ': ' . \App\Services\ActivityLogFormatter::format(
                $oldValues,
                $newValues,
                Product::class,
                $lockedProduct->id,
                'update_product_stock'
            );

            ActivityLog::create([
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()->branch_id,
                'action' => 'update_product_stock',
                'model_type' => Product::class,
                'model_id' => $lockedProduct->id,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [
                'size' => $productSize->size,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ];
        });

        return back()->with(
            'success',
            "Stok ukuran {$result['size']} berhasil diperbarui dari {$result['old_stock']} menjadi {$result['new_stock']}!"
        );
    }

    public function completeMaintenance(RentalItem $rentalItem)
{
    $user = Auth::user();

    if (!$user || (!$user->isSuperAdmin() && !$user->isAdminToko())) {
        abort(403, 'Unauthorized action.');
    }

    if ($rentalItem->maintenance_status !== 'pending') {
        return back()->with('error', 'Barang ini sudah selesai maintenance.');
    }

    DB::transaction(function () use ($rentalItem) {

        $item = RentalItem::query()
            ->lockForUpdate()
            ->findOrFail($rentalItem->id);

        if ($item->maintenance_status !== 'pending') {
            return;
        }

        $product = Product::query()
            ->lockForUpdate()
            ->findOrFail($item->product_id);

        $productSize = ProductSize::query()
            ->where('product_id', $product->id)
            ->where('size', $item->product_size)
            ->lockForUpdate()
            ->firstOrFail();

        $newStock = min(
            (int) $productSize->stock_available + (int) $item->quantity,
            (int) $productSize->stock_total
        );

        $product->setSizeStockAvailable($productSize, $newStock);

        $item->update([
            'maintenance_status' => 'completed',
        ]);
    });

    return back()->with(
        'success',
        'Maintenance selesai. Barang sudah dikembalikan ke stok tersedia.'
    );
}

    public function edit(Product $product)
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdminToko())) {
            abort(403, 'Unauthorized action.');
        }
        $this->ensureBranchAccess($product);
        $categories = Category::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $selectedBranches = $product->branches->pluck('id')->toArray();
        $product->load('sizes');
        $initialSizes = old('sizes', $product->sizesData);
        return view('products.edit', compact('product', 'categories', 'branches', 'selectedBranches', 'initialSizes'));
    }

    public function regenerateQr(Product $product)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        $this->generateQrCode($product);
        return back()->with('success', 'QR Code berhasil digenerate ulang!');
    }

    private function generateCode(): string
    {
        $last = Product::where('code', 'like', 'PRD-%')->orderBy('code', 'desc')->first();
        $seq = $last ? (int) preg_replace('/[^0-9]/', '', $last->code) + 1 : 1;

        return 'PRD-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    private function formatSizesData(array $data): array
    {
        $sizes = $data['sizes'] ?? [];

        return collect($sizes)->map(function ($stock, $sizeName) {
            return [
                'size' => $sizeName,
                'stock_total' => (int) $stock['stock_total'],
                'stock_available' => (int) $stock['stock_available'],
            ];
        })->values()->toArray();
    }

    public function destroy(Product $product)
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdminToko())) {
            abort(403, 'Unauthorized action.');
        }
        $this->ensureBranchAccess($product);

        $oldValues = [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'status' => $product->status,
            'stock_available' => (int) $product->stock_available,
            'stock_total' => (int) $product->stock_total,
        ];

        $hasRentalHistory = $product->rentalItems()->exists();

        if ($hasRentalHistory) {
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            if ($product->qr_code && Storage::disk('public')->exists($product->qr_code)) {
                Storage::disk('public')->delete($product->qr_code);
            }
            $product->delete();
        } else {
            try {
                $product->rentalItems()->update(['product_id' => null]);
                \App\Models\Chat::where('product_id', $product->id)->update(['product_id' => null]);
                \App\Models\Laundry::where('produk_id', $product->id)->update(['produk_id' => null]);
                DB::table('product_branch')->where('product_id', $product->id)->delete();

                if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                    Storage::disk('public')->delete($product->photo);
                }
                if ($product->qr_code && Storage::disk('public')->exists($product->qr_code)) {
                    Storage::disk('public')->delete($product->qr_code);
                }

                $product->forceDelete();
            } catch (\Illuminate\Database\QueryException $e) {
                $sqlState = $e->getCode();
                $isForeignKeyError = is_string($sqlState) && str_starts_with($sqlState, '23000');

                if ($isForeignKeyError) {
                    return back()->with('error', 'Produk tidak dapat dihapus permanen karena struktur database foreign key belum diperbarui. Silakan jalankan migration: php artisan migrate');
                }

                return back()->with('error', 'Produk tidak dapat dihapus permanen karena masih terhubung dengan data transaksi lain di database.');
            }
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'branch_id' => Auth::user()->branch_id,
            'action' => 'delete_product',
            'model_type' => Product::class,
            'model_id' => $oldValues['id'],
            'description' => \App\Services\ActivityLogFormatter::format($oldValues, null, Product::class, $oldValues['id'], 'delete_product'),
            'old_values' => $oldValues,
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('products.index')->with('success', $hasRentalHistory ? 'Produk berhasil diarsipkan (soft delete) karena memiliki history penyewaan.' : 'Produk berhasil dihapus permanen.');
    }

    private function ensureBranchAccess(Product $product): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if ($user->isSuperAdmin()) {
            return;
        }

        $allowedBranchIds = $user->accessible_branch_ids;
        $productBranchIds = $product->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
        if ($productBranchIds === [] && $product->branch_id) {
            $productBranchIds = [(int) $product->branch_id];
        }

        abort_unless(array_intersect($allowedBranchIds, $productBranchIds) !== [], 403);
    }

    private function generateQrCode(Product $product): void
    {
        $qrData = route('products.show', $product);
        $path = 'qrcodes/products/' . $product->code . '.svg';
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $svg = QrCode::format('svg')->size(200)->generate($qrData);
        file_put_contents($fullPath, $svg);
        $product->update(['qr_code' => $path]);
    }
}
