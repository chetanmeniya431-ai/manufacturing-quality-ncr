<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ncrs', function (Blueprint $table) {
            $table->enum('ai_suggestion_status', ['none', 'pending', 'ready', 'failed'])
                ->default('none')
                ->after('ai_root_cause_suggestion');
        });
    }

    public function down(): void
    {
        Schema::table('ncrs', function (Blueprint $table) {
            $table->dropColumn('ai_suggestion_status');
        });
    }
};
