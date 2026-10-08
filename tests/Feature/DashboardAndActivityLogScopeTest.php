<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class DashboardAndActivityLogScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_dashboard_applies_its_assigned_branch_and_selected_sales(): void
    {
        $branch = $this->makeBranch('Solo');
        $sales = $this->makeUser('sales', $branch);
        $admin = $this->makeUser('admin_toko', $branch);

        $service = Mockery::mock(DashboardService::class);
        $service->shouldReceive('buildPayload')
            ->once()
            ->with(Mockery::on(fn (array $filters) =>
                $filters['branch_ids'] === [$branch->id]
                && (int) $filters['sales_user_id'] === $sales->id
            ))
            ->andReturn(['stats' => [], 'charts' => [], 'widgets' => []]);
        $this->app->instance(DashboardService::class, $service);

        $this->actingAs($admin)
            ->getJson(route('dashboard.data', [
                'branch_id' => $branch->id,
                'sales_user_id' => $sales->id,
            ]))
            ->assertOk();
    }

    public function test_admin_dashboard_rejects_another_branch_and_its_sales(): void
    {
        $branchA = $this->makeBranch('Solo');
        $branchB = $this->makeBranch('Jogja');
        $admin = $this->makeUser('admin_toko', $branchA);
        $salesB = $this->makeUser('sales', $branchB);

        $this->actingAs($admin)
            ->getJson(route('dashboard.data', ['branch_id' => $branchB->id]))
            ->assertForbidden();

        $this->getJson(route('dashboard.data', ['sales_user_id' => $salesB->id]))
            ->assertForbidden();
    }

    public function test_sales_rental_activity_log_only_includes_rentals_created_by_that_sales(): void
    {
        $branch = $this->makeBranch('Solo');
        $otherBranch = $this->makeBranch('Jogja');
        $sales = $this->makeUser('sales', $branch);
        $otherSales = $this->makeUser('sales', $otherBranch);
        $ownRental = $this->makeRental($branch, $sales, 'OWN-001');
        $otherRental = $this->makeRental($otherBranch, $otherSales, 'OTHER-001');
        $this->makeRentalLog($ownRental, $sales, $branch, 'Aktivitas milik Sales ini');
        $this->makeRentalLog($otherRental, $otherSales, $otherBranch, 'Aktivitas milik Sales lain');

        $this->actingAs($sales)
            ->get(route('rentals.activity-log'))
            ->assertOk()
            ->assertSee('OWN-001')
            ->assertSee('Aktivitas milik Sales ini')
            ->assertDontSee('OTHER-001')
            ->assertDontSee('Aktivitas milik Sales lain');

        $this->get(route('reports.activity-log'))->assertForbidden();
    }

    public function test_activity_log_rejects_admin_branch_tampering_and_category_action_filter_works(): void
    {
        $branchA = $this->makeBranch('Solo');
        $branchB = $this->makeBranch('Jogja');
        $admin = $this->makeUser('admin_toko', $branchA);
        $sales = $this->makeUser('sales', $branchA);
        $rental = $this->makeRental($branchA, $sales, 'FILTER-001');
        $this->makeRentalLog($rental, $sales, $branchA, 'Rental dibuat', 'create_rental');
        $this->makeRentalLog($rental, $admin, $branchA, 'Rental diserahkan', 'handover_rental');

        $this->actingAs($admin)
            ->get(route('reports.activity-log', ['branch_id' => $branchB->id]))
            ->assertForbidden();

        $superAdmin = $this->makeUser('super_admin');
        $this->actingAs($superAdmin)
            ->get(route('reports.activity-log', ['tab' => 'rental', 'action' => 'Buat Sewa']))
            ->assertOk()
            ->assertSee('Rental dibuat')
            ->assertDontSee('Rental diserahkan');
    }

    private function makeBranch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(8)),
            'address' => 'Alamat test',
        ]);
    }

    private function makeUser(string $role, ?Branch $branch = null): User
    {
        return User::factory()->create([
            'name' => ucfirst($role) . ' Test',
            'email' => Str::uuid() . '@example.test',
            'role' => $role,
            'branch_id' => $branch?->id,
            'branch_access_type' => $role === 'super_admin' ? 'all' : 'specific',
            'is_active' => true,
        ]);
    }

    private function makeRental(Branch $branch, User $sales, string $invoice): Rental
    {
        $customer = Customer::create([
            'branch_id' => $branch->id,
            'name' => 'Customer ' . $invoice,
            'phone' => '081234567890',
        ]);

        return Rental::create([
            'invoice_number' => $invoice,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'created_by' => $sales->id,
            'rental_date' => today(),
            'return_due_date' => today()->addDay(),
            'duration_days' => 1,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'rental_status' => Rental::STATUS_WAITING,
        ]);
    }

    private function makeRentalLog(Rental $rental, User $actor, Branch $actorBranch, string $description, string $action = 'create_rental'): void
    {
        ActivityLog::withoutEvents(fn () => ActivityLog::create([
            'user_id' => $actor->id,
            'branch_id' => $actorBranch->id,
            'action' => $action,
            'model_type' => Rental::class,
            'model_id' => $rental->id,
            'description' => $description,
        ]));
    }
}
