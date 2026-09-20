<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncrs', function (Blueprint $table) {
            $table->id();
            $table->string('ncr_number')->unique();
            $table->string('product_name');
            $table->enum('defect_category', [
                'dimension_out_of_spec',
                'surface_defect',
                'material_non_conformance',
                'process_deviation',
                'supplier_defect',
                'documentation_error',
            ]);
            $table->text('description');
            $table->enum('severity', ['minor', 'major', 'critical']);
            $table->enum('detected_at', ['incoming', 'in_process', 'final', 'customer_return']);
            $table->foreignId('detected_by')->constrained('users');
            $table->date('detected_date');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->json('attachments')->nullable();

            $table->enum('status', [
                'open',
                'investigating',
                'corrective_assigned',
                'verification',
                'closed',
            ])->default('open');

            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->foreignId('corrective_action_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('corrective_action_due')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('closure_notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->json('ai_similar_ncrs')->nullable();
            $table->text('ai_root_cause_suggestion')->nullable();

            $table->timestamp('investigation_started_at')->nullable();
            $table->timestamp('corrective_action_assigned_at')->nullable();
            $table->timestamp('verification_started_at')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status']);
            $table->index(['severity']);
            $table->index(['defect_category']);
            $table->index(['detected_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncrs');
    }
};
