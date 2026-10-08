<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->string('maintenance_status')
                ->nullable()
                ->after('return_condition');
        });
    }
    
    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('maintenance_status');
        });
    }
};
