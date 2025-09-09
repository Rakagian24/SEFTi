<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\MemoPembayaran;
use App\Services\ApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApprovalWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $approvalWorkflowService;
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

        $this->approvalWorkflowService = new ApprovalWorkflowService();

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
    public function staff_toko_regular_department_workflow_has_correct_steps()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test workflow steps
        $workflow = $this->getWorkflowForMemoPembayaran($memo);

        $this->assertNotNull($workflow);
        $this->assertEquals(['verified', 'approved'], $workflow['steps']);
        $this->assertEquals(['Staff Toko', 'Kepala Toko', 'Kadiv'], $workflow['roles']);
    }

    /** @test */
    public function staff_toko_ziglo_department_workflow_has_correct_steps()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->ziGloDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->ziGloDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test workflow steps
        $workflow = $this->getWorkflowForMemoPembayaran($memo);

        $this->assertNotNull($workflow);
        $this->assertEquals(['approved'], $workflow['steps']);
        $this->assertEquals(['Staff Toko', 'Kadiv'], $workflow['roles']);
    }

    /** @test */
    public function staff_akunting_workflow_has_correct_steps()
    {
        // Create users
        $staffAkuntingUser = User::factory()->create(['role_id' => $this->staffAkuntingRole->id]);
        $staffAkuntingUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffAkuntingUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test workflow steps
        $workflow = $this->getWorkflowForMemoPembayaran($memo);

        $this->assertNotNull($workflow);
        $this->assertEquals(['approved'], $workflow['steps']);
        $this->assertEquals(['Staff Akunting & Finance', 'Kabag'], $workflow['roles']);
    }

    /** @test */
    public function staff_digital_marketing_workflow_has_correct_steps()
    {
        // Create users
        $staffDMUser = User::factory()->create(['role_id' => $this->staffDigitalMarketingRole->id]);
        $staffDMUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffDMUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test workflow steps
        $workflow = $this->getWorkflowForMemoPembayaran($memo);

        $this->assertNotNull($workflow);
        $this->assertEquals(['approved'], $workflow['steps']);
        $this->assertEquals(['Staff Digital Marketing', 'Kadiv'], $workflow['roles']);
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

        // Test that Kepala Toko can verify
        $canVerify = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kepalaTokoUser, $memo, 'verify');
        $this->assertTrue($canVerify);

        // Test that Kepala Toko cannot approve directly
        $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kepalaTokoUser, $memo, 'approve');
        $this->assertFalse($canApprove);
    }

    /** @test */
    public function kadiv_can_approve_after_kepala_toko_verification()
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
            'status' => 'Verified' // Already verified by Kepala Toko
        ]);

        // Test that Kadiv can approve after verification
        $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kadivUser, $memo, 'approve');
        $this->assertTrue($canApprove);

        // Test that Kadiv cannot verify (wrong step)
        $canVerify = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kadivUser, $memo, 'verify');
        $this->assertFalse($canVerify);
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

        // Test that Kadiv can directly approve Zi&Glo Staff Toko memo
        $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kadivUser, $memo, 'approve');
        $this->assertTrue($canApprove);
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

        // Test that Kabag can directly approve Staff Akunting memo
        $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kabagUser, $memo, 'approve');
        $this->assertTrue($canApprove);
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

        // Test that Kadiv can directly approve Staff Digital Marketing memo
        $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($kadivUser, $memo, 'approve');
        $this->assertTrue($canApprove);
    }

    /** @test */
    public function admin_can_perform_any_action()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test that Admin can perform any action
        $this->assertTrue($this->approvalWorkflowService->canUserApproveMemoPembayaran($adminUser, $memo, 'verify'));
        $this->assertTrue($this->approvalWorkflowService->canUserApproveMemoPembayaran($adminUser, $memo, 'approve'));
        $this->assertTrue($this->approvalWorkflowService->canUserApproveMemoPembayaran($adminUser, $memo, 'reject'));
    }

    /** @test */
    public function unauthorized_users_cannot_perform_actions()
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

        // Test that unauthorized user cannot perform actions
        $this->assertFalse($this->approvalWorkflowService->canUserApproveMemoPembayaran($unauthorizedUser, $memo, 'verify'));
        $this->assertFalse($this->approvalWorkflowService->canUserApproveMemoPembayaran($unauthorizedUser, $memo, 'approve'));
    }

    /** @test */
    public function rejection_is_allowed_for_any_role_in_workflow()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        $kepalaTokoUser = User::factory()->create(['role_id' => $this->kepalaTokoRole->id]);
        $kepalaTokoUser->departments()->attach($this->regularDepartment->id);

        $kadivUser = User::factory()->create(['role_id' => $this->kadivRole->id]);
        $kadivUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test that any role in workflow can reject
        $this->assertTrue($this->approvalWorkflowService->canUserApproveMemoPembayaran($kepalaTokoUser, $memo, 'reject'));
        $this->assertTrue($this->approvalWorkflowService->canUserApproveMemoPembayaran($kadivUser, $memo, 'reject'));
    }

    /** @test */
    public function approval_progress_shows_correct_steps()
    {
        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->staffTokoRole->id]);
        $staffTokoUser->departments()->attach($this->regularDepartment->id);

        // Create memo pembayaran
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->regularDepartment->id,
            'status' => 'In Progress'
        ]);

        // Test approval progress
        $progress = $this->approvalWorkflowService->getApprovalProgressForMemoPembayaran($memo);

        $this->assertCount(2, $progress); // verified, approved
        $this->assertEquals('verified', $progress[0]['step']);
        $this->assertEquals('Kepala Toko', $progress[0]['role']);
        $this->assertEquals('current', $progress[0]['status']);

        $this->assertEquals('approved', $progress[1]['step']);
        $this->assertEquals('Kadiv', $progress[1]['role']);
        $this->assertEquals('pending', $progress[1]['status']);
    }

    // Helper method to access private method for testing
    private function getWorkflowForMemoPembayaran(MemoPembayaran $memo)
    {
        $reflection = new \ReflectionClass($this->approvalWorkflowService);
        $method = $reflection->getMethod('getWorkflowForMemoPembayaran');
        $method->setAccessible(true);

        return $method->invoke($this->approvalWorkflowService, $memo);
    }
}
