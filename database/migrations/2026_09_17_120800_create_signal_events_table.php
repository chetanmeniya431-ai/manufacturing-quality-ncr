<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ncr_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            // Dedup/group key for signals that aren't tied to one NCR or
            // supplier row (e.g. "5+ NCRs in this defect category this
            // month", "this month's NCR count spiked") — e.g. {"category":
            // "dimension_out_of_spec"} or {"month": "2026-09"}.
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_events');
    }
};
