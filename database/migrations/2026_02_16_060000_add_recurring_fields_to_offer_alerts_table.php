<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecurringFieldsToOfferAlertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('offer_alerts', 'frequency')) {
                $table->string('frequency')->default('once')->after('notify_on_any_drop');
            }

            if (!Schema::hasColumn('offer_alerts', 'last_notified_at')) {
                $table->timestamp('last_notified_at')->nullable()->after('last_notified_price');
            }

            if (!Schema::hasColumn('offer_alerts', 'recurring_window_started_at')) {
                $table->timestamp('recurring_window_started_at')->nullable()->after('last_notified_at');
            }

            if (!Schema::hasColumn('offer_alerts', 'recurring_popup_pending')) {
                $table->boolean('recurring_popup_pending')->default(false)->after('recurring_window_started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offer_alerts', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('offer_alerts', 'recurring_popup_pending')) {
                $columns[] = 'recurring_popup_pending';
            }

            if (Schema::hasColumn('offer_alerts', 'recurring_window_started_at')) {
                $columns[] = 'recurring_window_started_at';
            }

            if (Schema::hasColumn('offer_alerts', 'last_notified_at')) {
                $columns[] = 'last_notified_at';
            }

            if (Schema::hasColumn('offer_alerts', 'frequency')) {
                $columns[] = 'frequency';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
