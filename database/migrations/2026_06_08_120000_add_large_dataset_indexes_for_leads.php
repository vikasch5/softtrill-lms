<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['added_by', 'id'], 'leads_added_by_id_idx');
            $table->index(['added_by', 'list_id', 'id'], 'leads_added_by_list_id_id_idx');
            $table->index(['tenant_id', 'phone_index'], 'leads_tenant_phone_index_idx');
            $table->index(['tenant_id', 'assigned_to', 'id'], 'leads_tenant_assigned_to_id_idx');
            $table->index(['tenant_id', 'status', 'id'], 'leads_tenant_status_id_idx');
            $table->index(['tenant_id', 'next_followup_at', 'id'], 'leads_tenant_next_followup_id_idx');
        });

        Schema::table('lead_fields', function (Blueprint $table) {
            $table->index(['list_id', 'sort_order', 'id'], 'lead_fields_list_sort_order_id_idx');
        });

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->index(['lead_id', 'id'], 'lead_followups_lead_id_id_idx');
            $table->index(['lead_id', 'followup_at', 'id'], 'lead_followups_lead_followup_at_id_idx');
        });

        Schema::table('lead_feedbacks', function (Blueprint $table) {
            $table->index(['lead_id', 'id'], 'lead_feedbacks_lead_id_id_idx');
        });

        Schema::table('lead_activity_logs', function (Blueprint $table) {
            $table->index(['lead_id', 'id'], 'lead_activity_logs_lead_id_id_idx');
        });

        Schema::table('lead_notes', function (Blueprint $table) {
            $table->index(['lead_id', 'id'], 'lead_notes_lead_id_id_idx');
        });

        Schema::table('lead_import_files', function (Blueprint $table) {
            $table->index(['tenant_id', 'id'], 'lead_import_files_tenant_id_id_idx');
            $table->index(['list_id', 'id'], 'lead_import_files_list_id_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_import_files', function (Blueprint $table) {
            $table->dropIndex('lead_import_files_list_id_id_idx');
            $table->dropIndex('lead_import_files_tenant_id_id_idx');
        });

        Schema::table('lead_notes', function (Blueprint $table) {
            $table->dropIndex('lead_notes_lead_id_id_idx');
        });

        Schema::table('lead_activity_logs', function (Blueprint $table) {
            $table->dropIndex('lead_activity_logs_lead_id_id_idx');
        });

        Schema::table('lead_feedbacks', function (Blueprint $table) {
            $table->dropIndex('lead_feedbacks_lead_id_id_idx');
        });

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dropIndex('lead_followups_lead_followup_at_id_idx');
            $table->dropIndex('lead_followups_lead_id_id_idx');
        });

        Schema::table('lead_fields', function (Blueprint $table) {
            $table->dropIndex('lead_fields_list_sort_order_id_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_tenant_next_followup_id_idx');
            $table->dropIndex('leads_tenant_status_id_idx');
            $table->dropIndex('leads_tenant_assigned_to_id_idx');
            $table->dropIndex('leads_tenant_phone_index_idx');
            $table->dropIndex('leads_added_by_list_id_id_idx');
            $table->dropIndex('leads_added_by_id_idx');
        });
    }
};
