<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DepartmentService;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class DepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_all_department_can_access_all_departments()
    {
        // Create departments
        $dept1 = Department::factory()->create(['name' => 'IT']);
        $dept2 = Department::factory()->create(['name' => 'HR']);
        $dept3 = Department::factory()->create(['name' => 'Finance']);
        $allDept = Department::factory()->create(['name' => 'All']);

        // Create user with 'All' department
        $user = User::factory()->create();
        $user->departments()->attach($allDept->id);

        // Login user
        Auth::login($user);

        // Test form options (should show all departments)
        $formOptions = DepartmentService::getOptionsForForm();
        $this->assertCount(3, $formOptions); // IT, HR, Finance (exclude 'All')
        $this->assertContains('IT', array_column($formOptions, 'name'));
        $this->assertContains('HR', array_column($formOptions, 'name'));
        $this->assertContains('Finance', array_column($formOptions, 'name'));

        // Test filter options (should show 'All' option + all departments)
        $filterOptions = DepartmentService::getOptionsForUser(true);
        $this->assertCount(4, $filterOptions); // 'All' + IT, HR, Finance
        $this->assertContains('Semua Departemen', array_column($filterOptions, 'name'));
        $this->assertContains('IT', array_column($filterOptions, 'name'));
        $this->assertContains('HR', array_column($filterOptions, 'name'));
        $this->assertContains('Finance', array_column($filterOptions, 'name'));
    }

    public function test_user_with_single_department_gets_only_that_department()
    {
        // Create department
        $dept = Department::factory()->create(['name' => 'IT']);

        // Create user with single department
        $user = User::factory()->create();
        $user->departments()->attach($dept->id);

        // Login user
        Auth::login($user);

        // Test form options
        $formOptions = DepartmentService::getOptionsForForm();
        $this->assertCount(1, $formOptions);
        $this->assertEquals('IT', $formOptions[0]['name']);
    }

    public function test_user_with_multiple_departments_gets_their_departments()
    {
        // Create departments
        $dept1 = Department::factory()->create(['name' => 'IT']);
        $dept2 = Department::factory()->create(['name' => 'HR']);

        // Create user with multiple departments
        $user = User::factory()->create();
        $user->departments()->attach([$dept1->id, $dept2->id]);

        // Login user
        Auth::login($user);

        // Test form options
        $formOptions = DepartmentService::getOptionsForForm();
        $this->assertCount(2, $formOptions);
        $this->assertContains('IT', array_column($formOptions, 'name'));
        $this->assertContains('HR', array_column($formOptions, 'name'));

        // Test filter options with 'All' option
        $filterOptions = DepartmentService::getOptionsForUser(true);
        $this->assertCount(3, $filterOptions); // 'All' + IT, HR
        $this->assertContains('Semua Departemen Saya', array_column($filterOptions, 'name'));
    }

    public function test_user_without_departments_gets_empty_array()
    {
        // Create user without departments
        $user = User::factory()->create();

        // Login user
        Auth::login($user);

        // Test options
        $formOptions = DepartmentService::getOptionsForForm();
        $this->assertCount(0, $formOptions);
    }

    public function test_user_access_validation()
    {
        // Create departments
        $dept1 = Department::factory()->create(['name' => 'IT']);
        $dept2 = Department::factory()->create(['name' => 'HR']);
        $allDept = Department::factory()->create(['name' => 'All']);

        // Create user with 'All' department
        $user = User::factory()->create();
        $user->departments()->attach($allDept->id);

        // Login user
        Auth::login($user);

        // User with 'All' should have access to any department
        $this->assertTrue(DepartmentService::userHasAccess($dept1->id));
        $this->assertTrue(DepartmentService::userHasAccess($dept2->id));
        $this->assertTrue(DepartmentService::userHasAccess(999)); // Even non-existent
    }
} 