<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->createRoles();
    }

    private function createRoles()
    {
        $roles = [
            [
                'name' => 'Staff Toko',
                'permissions' => ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran'],
            ],
            [
                'name' => 'Kepala Toko',
                'permissions' => ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran', 'approval'],
            ],
            [
                'name' => 'Staff Akunting & Finance',
                'permissions' => ['purchase_order', 'bank', 'supplier', 'bisnis_partner', 'memo_pembayaran', 'bpb', 'payment_voucher', 'daftar_list_bayar', 'bank_masuk', 'bank_keluar', 'po_outstanding'],
            ],
            [
                'name' => 'Kabag',
                'permissions' => ['purchase_order', 'bank', 'supplier', 'bisnis_partner', 'memo_pembayaran', 'bpb', 'payment_voucher', 'daftar_list_bayar', 'bank_masuk', 'bank_keluar', 'po_outstanding', 'approval'],
            ],
            [
                'name' => 'Kadiv',
                'permissions' => ['approval'],
            ],
            [
                'name' => 'Direksi',
                'permissions' => ['approval'],
            ],
            [
                'name' => 'Admin',
                'permissions' => ['*'],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }

    /** @test */
    public function staff_toko_can_access_purchase_order()
    {
        $role = Role::where('name', 'Staff Toko')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        $response = $this->get('/purchase-orders');
        $response->assertStatus(200);
    }

    /** @test */
    public function staff_toko_cannot_access_bank()
    {
        $role = Role::where('name', 'Staff Toko')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        $response = $this->get('/banks');
        $response->assertStatus(403);
    }

    /** @test */
    public function staff_akunting_can_access_bank_and_supplier()
    {
        $role = Role::where('name', 'Staff Akunting & Finance')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        $response = $this->get('/banks');
        $response->assertStatus(200);

        $response = $this->get('/suppliers');
        $response->assertStatus(200);
    }

    /** @test */
    public function kabag_can_access_approval()
    {
        $role = Role::where('name', 'Kabag')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        // Assuming approval route exists
        $response = $this->get('/approval');
        $response->assertStatus(200);
    }

    /** @test */
    public function kadiv_can_only_access_approval()
    {
        $role = Role::where('name', 'Kadiv')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        // Should access approval
        $response = $this->get('/approval');
        $response->assertStatus(200);

        // Should not access other routes
        $response = $this->get('/banks');
        $response->assertStatus(403);

        $response = $this->get('/purchase-orders');
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_all_routes()
    {
        $role = Role::where('name', 'Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        // Test access to various routes
        $routes = [
            '/banks',
            '/suppliers',
            '/purchase-orders',
            '/roles',
            '/departments',
            '/users',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function user_without_role_cannot_access_protected_routes()
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user);

        $response = $this->get('/banks');
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->get('/banks');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_model_has_permission_methods()
    {
        $role = Role::where('name', 'Staff Akunting & Finance')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        // Test hasPermission
        $this->assertTrue($user->hasPermission('bank'));
        $this->assertFalse($user->hasPermission('approval'));

        // Test hasAnyPermission
        $this->assertTrue($user->hasAnyPermission(['bank', 'approval']));
        $this->assertFalse($user->hasAnyPermission(['approval', 'admin']));

        // Test hasRole
        $this->assertTrue($user->hasRole('Staff Akunting & Finance'));
        $this->assertFalse($user->hasRole('Admin'));

        // Test hasAnyRole
        $this->assertTrue($user->hasAnyRole(['Staff Akunting & Finance', 'Admin']));
        $this->assertFalse($user->hasAnyRole(['Kadiv', 'Direksi']));
    }

    /** @test */
    public function admin_user_has_all_permissions()
    {
        $role = Role::where('name', 'Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('bank'));
        $this->assertTrue($user->hasPermission('approval'));
        $this->assertTrue($user->hasPermission('purchase_order'));
        $this->assertTrue($user->hasPermission('non_existent_permission'));
    }

    /** @test */
    public function middleware_handles_multiple_permissions()
    {
        $role = Role::where('name', 'Staff Akunting & Finance')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        // Should access routes that require bank OR supplier permission
        $response = $this->get('/banks');
        $response->assertStatus(200);

        $response = $this->get('/suppliers');
        $response->assertStatus(200);
    }
}
