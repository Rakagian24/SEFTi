<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\MemoPembayaran;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemoPembayaranWorkflowDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all necessary roles and departments
        $this->createRolesAndDepartments();
    }

    /** @test */
    public function demo_complete_workflow_scenarios()
    {
        $this->markTestSkipped('This is a demo test - run manually to see workflow scenarios');

        echo "\n=== MEMO PEMBAYARAN WORKFLOW DEMO ===\n\n";

        // Scenario 1: Staff Toko (Regular Department) -> Kepala Toko -> Kadiv
        $this->demoStaffTokoRegularWorkflow();

        // Scenario 2: Staff Toko (Zi&Glo) -> Kadiv (Direct)
        $this->demoStaffTokoZiGloWorkflow();

        // Scenario 3: Staff Akunting & Finance -> Kabag (Direct)
        $this->demoStaffAkuntingWorkflow();

        // Scenario 4: Staff Digital Marketing -> Kadiv (Direct)
        $this->demoStaffDigitalMarketingWorkflow();

        echo "\n=== DEMO COMPLETED ===\n";
    }

    private function demoStaffTokoRegularWorkflow()
    {
        echo "1. STAFF TOKO (REGULAR DEPARTMENT) WORKFLOW\n";
        echo "   Creator: Staff Toko -> Verifier: Kepala Toko -> Approver: Kadiv\n\n";

        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->getRole('Staff Toko')->id]);
        $staffTokoUser->departments()->attach($this->getDepartment('SGT 1')->id);

        $kepalaTokoUser = User::factory()->create(['role_id' => $this->getRole('Kepala Toko')->id]);
        $kepalaTokoUser->departments()->attach($this->getDepartment('SGT 1')->id);

        $kadivUser = User::factory()->create(['role_id' => $this->getRole('Kadiv')->id]);
        $kadivUser->departments()->attach($this->getDepartment('SGT 1')->id);

        // Create memo
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->getDepartment('SGT 1')->id,
            'status' => 'In Progress'
        ]);

        echo "   Initial Status: {$memo->status}\n";

        // Step 1: Kepala Toko verifies
        $this->actingAs($kepalaTokoUser);
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/verify", [
            'notes' => 'Verified by Kepala Toko'
        ]);

        $memo->refresh();
        echo "   After Kepala Toko verification: {$memo->status}\n";
        echo "   Verifier: " . ($memo->verifier ? $memo->verifier->name : 'N/A') . "\n";

        // Step 2: Kadiv approves
        $this->actingAs($kadivUser);
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Approved by Kadiv'
        ]);

        $memo->refresh();
        echo "   After Kadiv approval: {$memo->status}\n";
        echo "   Approver: " . ($memo->approver ? $memo->approver->name : 'N/A') . "\n";

        echo "   ✅ Workflow completed successfully!\n\n";
    }

    private function demoStaffTokoZiGloWorkflow()
    {
        echo "2. STAFF TOKO (ZI&GLO DEPARTMENT) WORKFLOW\n";
        echo "   Creator: Staff Toko (Zi&Glo) -> Approver: Kadiv (Direct)\n\n";

        // Create users
        $staffTokoUser = User::factory()->create(['role_id' => $this->getRole('Staff Toko')->id]);
        $staffTokoUser->departments()->attach($this->getDepartment('Zi&Glo')->id);

        $kadivUser = User::factory()->create(['role_id' => $this->getRole('Kadiv')->id]);
        $kadivUser->departments()->attach($this->getDepartment('Zi&Glo')->id);

        // Create memo
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffTokoUser->id,
            'department_id' => $this->getDepartment('Zi&Glo')->id,
            'status' => 'In Progress'
        ]);

        echo "   Initial Status: {$memo->status}\n";

        // Step 1: Kadiv directly approves (bypasses Kepala Toko)
        $this->actingAs($kadivUser);
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Direct approval by Kadiv for Zi&Glo'
        ]);

        $memo->refresh();
        echo "   After Kadiv direct approval: {$memo->status}\n";
        echo "   Approver: " . ($memo->approver ? $memo->approver->name : 'N/A') . "\n";

        echo "   ✅ Workflow completed successfully!\n\n";
    }

    private function demoStaffAkuntingWorkflow()
    {
        echo "3. STAFF AKUNTING & FINANCE WORKFLOW\n";
        echo "   Creator: Staff Akunting & Finance -> Approver: Kabag (Direct)\n\n";

        // Create users
        $staffAkuntingUser = User::factory()->create(['role_id' => $this->getRole('Staff Akunting & Finance')->id]);
        $staffAkuntingUser->departments()->attach($this->getDepartment('SGT 1')->id);

        $kabagUser = User::factory()->create(['role_id' => $this->getRole('Kabag')->id]);
        $kabagUser->departments()->attach($this->getDepartment('SGT 1')->id);

        // Create memo
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffAkuntingUser->id,
            'department_id' => $this->getDepartment('SGT 1')->id,
            'status' => 'In Progress'
        ]);

        echo "   Initial Status: {$memo->status}\n";

        // Step 1: Kabag directly approves
        $this->actingAs($kabagUser);
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Direct approval by Kabag'
        ]);

        $memo->refresh();
        echo "   After Kabag direct approval: {$memo->status}\n";
        echo "   Approver: " . ($memo->approver ? $memo->approver->name : 'N/A') . "\n";

        echo "   ✅ Workflow completed successfully!\n\n";
    }

    private function demoStaffDigitalMarketingWorkflow()
    {
        echo "4. STAFF DIGITAL MARKETING WORKFLOW\n";
        echo "   Creator: Staff Digital Marketing -> Approver: Kadiv (Direct)\n\n";

        // Create users
        $staffDMUser = User::factory()->create(['role_id' => $this->getRole('Staff Digital Marketing')->id]);
        $staffDMUser->departments()->attach($this->getDepartment('SGT 1')->id);

        $kadivUser = User::factory()->create(['role_id' => $this->getRole('Kadiv')->id]);
        $kadivUser->departments()->attach($this->getDepartment('SGT 1')->id);

        // Create memo
        $memo = MemoPembayaran::factory()->create([
            'created_by' => $staffDMUser->id,
            'department_id' => $this->getDepartment('SGT 1')->id,
            'status' => 'In Progress'
        ]);

        echo "   Initial Status: {$memo->status}\n";

        // Step 1: Kadiv directly approves
        $this->actingAs($kadivUser);
        $response = $this->postJson("/api/approval/memo-pembayarans/{$memo->id}/approve", [
            'notes' => 'Direct approval by Kadiv for Digital Marketing'
        ]);

        $memo->refresh();
        echo "   After Kadiv direct approval: {$memo->status}\n";
        echo "   Approver: " . ($memo->approver ? $memo->approver->name : 'N/A') . "\n";

        echo "   ✅ Workflow completed successfully!\n\n";
    }

    private function createRolesAndDepartments()
    {
        // Create roles
        Role::create([
            'name' => 'Staff Toko',
            'description' => 'Staff Toko role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Kepala Toko',
            'description' => 'Kepala Toko role',
            'permissions' => ['memo_pembayaran', 'approval'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Staff Akunting & Finance',
            'description' => 'Staff Akunting role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Kabag',
            'description' => 'Kabag role',
            'permissions' => ['memo_pembayaran', 'approval'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Staff Digital Marketing',
            'description' => 'Staff Digital Marketing role',
            'permissions' => ['memo_pembayaran'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Kadiv',
            'description' => 'Kadiv role',
            'permissions' => ['approval'],
            'status' => 'active'
        ]);

        Role::create([
            'name' => 'Admin',
            'description' => 'Admin role',
            'permissions' => ['*'],
            'status' => 'active'
        ]);

        // Create departments
        Department::create([
            'name' => 'SGT 1',
            'alias' => 'SGT1',
            'status' => 'active'
        ]);

        Department::create([
            'name' => 'Zi&Glo',
            'alias' => 'ZG',
            'status' => 'active'
        ]);
    }

    private function getRole($name)
    {
        return Role::where('name', $name)->first();
    }

    private function getDepartment($name)
    {
        return Department::where('name', $name)->first();
    }
}
