<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('offer_alerts')->where('status', 'monitoring')->update(['status' => 'active']);
        DB::table('offer_alerts')->where('status', 'paused')->update(['status' => 'inactive']);
        DB::table('offer_alerts')->where('status', 'stopped')->update(['status' => 'inactive']);
        DB::table('offer_alerts')->where('status', 'fulfilled')->update(['status' => 'triggered']);

        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'monitoring')
            ->update(['plan_deactivated_from_status' => 'active']);
        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'paused')
            ->update(['plan_deactivated_from_status' => 'inactive']);
        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'stopped')
            ->update(['plan_deactivated_from_status' => 'inactive']);
        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'fulfilled')
            ->update(['plan_deactivated_from_status' => 'triggered']);
    }

    public function down(): void
    {
        DB::table('offer_alerts')->where('status', 'active')->update(['status' => 'monitoring']);
        DB::table('offer_alerts')->where('status', 'inactive')->update(['status' => 'paused']);
        DB::table('offer_alerts')->where('status', 'triggered')->update(['status' => 'fulfilled']);
        DB::table('offer_alerts')->where('status', 'fallback_email')->update(['status' => 'paused']);

        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'active')
            ->update(['plan_deactivated_from_status' => 'monitoring']);
        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'inactive')
            ->update(['plan_deactivated_from_status' => 'paused']);
        DB::table('offer_alerts')->where('plan_deactivated_from_status', 'triggered')
            ->update(['plan_deactivated_from_status' => 'fulfilled']);
    }
};
