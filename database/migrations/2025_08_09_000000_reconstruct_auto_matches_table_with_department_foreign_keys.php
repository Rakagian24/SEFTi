<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add the new department_id column
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('invoice_customer_name')->constrained('departments')->onDelete('set null');
        });

        // Migrate existing data from string fields to foreign key
        $this->migrateDepartmentData();

        // Drop the old string department columns
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->dropColumn(['invoice_department', 'bank_masuk_department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old string columns
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->string('invoice_department')->nullable()->after('invoice_customer_name');
            $table->string('bank_masuk_department')->nullable()->after('bank_masuk_customer_name');
        });

        // Migrate data back from foreign key to string fields
        $this->rollbackDepartmentData();

        // Drop the foreign key column
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }

    /**
     * Migrate department data from string fields to foreign key
     */
    private function migrateDepartmentData(): void
    {
        // Get all auto_matches records
        $autoMatches = DB::table('auto_matches')->get();

        foreach ($autoMatches as $autoMatch) {
            $departmentId = null;

            // Try to find department by invoice_department first
            if ($autoMatch->invoice_department) {
                $department = DB::table('departments')
                    ->where('name', $autoMatch->invoice_department)
                    ->orWhere('alias', $autoMatch->invoice_department)
                    ->first();

                if ($department) {
                    $departmentId = $department->id;
                }
            }

            // If not found, try bank_masuk_department
            if (!$departmentId && $autoMatch->bank_masuk_department) {
                $department = DB::table('departments')
                    ->where('name', $autoMatch->bank_masuk_department)
                    ->orWhere('alias', $autoMatch->bank_masuk_department)
                    ->first();

                if ($department) {
                    $departmentId = $department->id;
                }
            }

            // Update the record with the found department_id
            if ($departmentId) {
                DB::table('auto_matches')
                    ->where('id', $autoMatch->id)
                    ->update(['department_id' => $departmentId]);
            }
        }
    }

    /**
     * Rollback department data from foreign key to string fields
     */
    private function rollbackDepartmentData(): void
    {
        // Get all auto_matches records with department_id
        $autoMatches = DB::table('auto_matches')
            ->join('departments', 'auto_matches.department_id', '=', 'departments.id')
            ->select('auto_matches.id', 'departments.name as department_name')
            ->get();

        foreach ($autoMatches as $autoMatch) {
            DB::table('auto_matches')
                ->where('id', $autoMatch->id)
                ->update([
                    'invoice_department' => $autoMatch->department_name,
                    'bank_masuk_department' => $autoMatch->department_name
                ]);
        }
    }
};
