<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update general_settings site_name and email
        if (Schema::hasTable('general_settings')) {
            $gs = DB::table('general_settings')->first();
            if ($gs) {
                DB::table('general_settings')->where('id', $gs->id)->update([
                    'site_name' => 'ToolsByDcx',
                    'email_from' => str_ireplace('wemate', 'toolsbydcx', $gs->email_from ?? 'info@toolsbydcx.com'),
                ]);
            }
        }

        // 2. Update frontend contents and sections
        if (Schema::hasTable('frontends')) {
            $frontends = DB::table('frontends')->get();
            foreach ($frontends as $item) {
                $raw = $item->data_values;
                if ($raw && (stripos($raw, 'wemate') !== false || stripos($raw, 'shahabtech') !== false)) {
                    $replaced = str_ireplace(['wemate', 'shahabtech'], ['ToolsByDcx', 'ToolsByDcx'], $raw);
                    DB::table('frontends')->where('id', $item->id)->update(['data_values' => $replaced]);
                }
            }
        }

        // 3. Update notification/email templates
        if (Schema::hasTable('notification_templates')) {
            $templates = DB::table('notification_templates')->get();
            foreach ($templates as $t) {
                $subj = str_ireplace(['wemate', 'shahabtech'], ['ToolsByDcx', 'ToolsByDcx'], $t->subj ?? '');
                $emailBody = str_ireplace(['wemate', 'shahabtech'], ['ToolsByDcx', 'ToolsByDcx'], $t->email_body ?? '');
                $smsBody = str_ireplace(['wemate', 'shahabtech'], ['ToolsByDcx', 'ToolsByDcx'], $t->sms_body ?? '');
                DB::table('notification_templates')->where('id', $t->id)->update([
                    'subj' => $subj,
                    'email_body' => $emailBody,
                    'sms_body' => $smsBody,
                ]);
            }
        }

        // 4. Update pages table
        if (Schema::hasTable('pages')) {
            $pages = DB::table('pages')->get();
            foreach ($pages as $p) {
                $name = str_ireplace(['wemate', 'shahabtech'], ['ToolsByDcx', 'ToolsByDcx'], $p->name ?? '');
                DB::table('pages')->where('id', $p->id)->update([
                    'name' => $name,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
