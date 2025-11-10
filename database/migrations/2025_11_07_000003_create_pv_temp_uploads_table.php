<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pv_temp_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 50);
            $table->string('path_tmp');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->timestamps();
            $table->index(['session_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pv_temp_uploads');
    }
};
