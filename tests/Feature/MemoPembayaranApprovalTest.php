<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\MemoPembayaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class MemoPembayaranApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected $staffTokoRole;
    protected $kepalaTokoRole;
    protected $staffAkuntingRole;
    protected $kabagRole;
    protected $staffDigitalMarketingRole;
    protected $kadivRole;
    protected $adminRole;
    protected $regularDepartment;
    protected $ziGloDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->staffTokoRole = Role::create([
            'name' => 'Staff Toko',
            'description' => 'Staff Toko role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        $this->kepalaTokoRole = Role::create([
            'name' => 'Kepala Toko',
            'description' => 'Kepala Toko role',
            'permissions' => ['memo_pembayaran', 'approval'],
            'status' => 'active'
        ]);

        $this->staffAkuntingRole = Role::create([
            'name' => 'Staff Akunting & Finance',
            'description' => 'Staff Akunting role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        $this->kabagRole = Role::create([
            'name' => 'Kabag',
            'description' => 'Kabag role',
            'permissions' => ['memo_pembayaran', 'approval'],
            'status' => 'active'
        ]);

        $this->staffDigitalMarketingRole = Role::create([
            'name' => 'Staff Digital Marketing',
            'description' => 'Staff Digital Marketing role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        $this->kadivRole = Role::create([
            'name' => 'Kadiv',
            'description' => 'Kadiv role',
            'permissions' => ['approval'],
            'status' => 'active'
        ]);

        $this->adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'Admin role',
            'permissions' => ['*'],
            'status' => 'active'
        ]);

        // Create departments
        $this->regularDepartment = Department::create([
            'name' => 'SGT 1',
            'alias' => 'SGT1',
            'status' => 'active'
        ]);

        $this->ziGloDepartment = Department::create([
            'name' => 'Zi&Glo',
            'alias' => 'ZG',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function kepala_toko_can_verify_staff_toko_memo()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kepalaTokoUser = User::factory()->create(['role_id' => $this->kepalaTokoRole->id]);
        $kepalaTokoUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kepala Toko
        $this->actingAs($kepalaTokoUser);

        // Test verify endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/verify", [
            'notes' => 'Verified by Kepala Toko'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran verified successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Verified', $memo->status);
        $this->assertEquals($kepalaTokoUser->id, $memo->verified_by);
    }

    /** @test */
    public function kadiv_can_approve_after_verification()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'Verified'
        ]);

        // Login as Kadiv
        $this->actingAs($kadivUser);

        // Test approve endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Approved by Kadiv'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran approved successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Approved', $memo->status);
        $this->assertEquals($kadivUser->id, $memo->approved_by);
    }

    /** @test */
    public function kadiv_can_directly_approve_ziglo_staff_toko_memo()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->ziGloDepartment->id);

        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->ziGloDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->ziGloDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kadiv
        $this->actingAs($kadivUser);

        // Test approve endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Approved by Kadiv for Zi&Glo'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran approved successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Approved', $memo->status);
        $this->assertEquals($kadivUser->id, $memo->approved_by);
    }

    /** @test */
    public function kabag_can_directly_approve_staff_akunting_memo()
    {
        // Create users
        $staffAkuntingUser = User::factory()->create(['role_id' => $this->staffAkuntingRole->id]);
        $staffAkuntingUser->departments()->attach($this->regularDepartment->id);

        $kabagUser = User::factory()->create(['role_id' => $this->kabagRole->id]);
        $kabagUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffAkuntingUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kabag
        $this->actingAs($kabagUser);

        // Test approve endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Approved by Kabag'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran approved successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Approved', $memo->status);
        $this->assertEquals($kabagUser->id, $memo->approved_by);
    }

    /** @test */
    public function kadiv_can_directly_approve_staff_digital_marketing_memo()
    {
        // Create users
        $staffDMUser = User::factory()->create(['role_id' => $this->staffDigitalMarketingRole->id]);
        $staffDMUser->departments()->attach($this->regularDepartment->id);

        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffDMUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kadiv
        $this->actingAs($kadivUser);

        // Test approve endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Approved by Kadiv for Digital Marketing'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran approved successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Approved', $memo->status);
        $this->assertEquals($kadivUser->id, $memo->approved_by);
    }

    /** @test */
    public function unauthorized_user_cannot_approve()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $unauthorizedUser = User::factory()->create(['role_id' => $this->staffAkuntingRole->id]);
        $unauthorizedUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as unauthorized user
        $this->actingAs($unauthorizedUser);

        // Test approve endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Unauthorized approval attempt'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Unauthorized to approve this memo'
        ]);
    }

    /** @test */
    public function bulk_approve_works_correctly()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kepalaTokoUser = User::factory()->create(['role_id' => $this->kepalaTokoRole->id]);
        $kepalaTokoUser->departments()->attach($this->regularDepartment->id);

        // Create multiple memo pembayarans
        $memo1 = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        $memo2 = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kepala Toko
        $this->actingAs($kepalaTokoUser);

        // Test bulk verify endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/bulk-verify", [
            'memo_ids' => [$memo1->id, $memo2->id],
            'notes' => 'Bulk verified by Kepala Toko'
        ]);

        $response->assertStatus(200);

        // Verify status was updated
        $memo1->refresh();
        $memo2->refresh();
        $this->assertEquals('Verified', $memo1->status);
        $this->assertEquals('Verified', $memo2->status);
    }

    /** @test */
    public function rejection_works_for_any_role_in_workflow()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kepalaTokoUser = User::factory()->create(['role_id' => $this->kepalaTokoRole->id]);
        $kepalaTokoUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kepala Toko
        $this->actingAs($kepalaTokoUser);

        // Test reject endpoint
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/reject", [
            'reason' => 'Rejected by Kepala Toko'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Memo Pembayaran rejected successfully'
        ]);

        // Verify status was updated
        $memo->refresh();
        $this->assertEquals('Rejected', $memo->status);
        $this->assertEquals($kepalaTokoUser->id, $memo->rejected_by);
    }

    /** @test */
    public function approval_progress_endpoint_works()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kadiv
        $this->actingAs($kadivUser);

        // Test approval progress endpoint
        $response = $this->getJson("/api/approval/memo-pembayarans/{$memo->id}/progress");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'progress' => [
                '*' => [
                    'step',
                    'role',
                    'status',
                    'completed_at',
                    'completed_by'
                ]
            ],
            'current_status'
        ]);
    }

    /** @test */
    public function get_memo_pembayarans_endpoint_works()
    {
        // Create users
        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Login as Kadiv
        $this->actingAs($kadivUser);

        // Test get memo pembayarans endpoint
        $response = $this->getJson("/api/approval/memo-pembayarans");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'no_mb',
                    'status',
                    'department',
                    'created_at'
                ]
            ],
            'pagination',
            'counts'
        ]);
    }
}
