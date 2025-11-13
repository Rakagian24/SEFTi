<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('dp_active')->default(false)->after('pph_nominal');
            $table->string('dp_type')->nullable()->after('dp_active'); // 'percent' | 'nominal'
            $table->decimal('dp_percent', 15, 5)->nullable()->after('dp_type');
            $table->decimal('dp_nominal', 20, 5)->nullable()->after('dp_percent');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'dp_active')) $table->dropColumn('dp_active');
            if (Schema::hasColumn('purchase_orders', 'dp_type')) $table->dropColumn('dp_type');
            if (Schema::hasColumn('purchase_orders', 'dp_percent')) $table->dropColumn('dp_percent');
            if (Schema::hasColumn('purchase_orders', 'dp_nominal')) $table->dropColumn('dp_nominal');
        });
    }
};
