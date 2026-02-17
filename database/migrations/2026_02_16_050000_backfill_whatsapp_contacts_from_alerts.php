<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillWhatsAppContactsFromAlerts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!DB::getSchemaBuilder()->hasTable('whatsapp_contacts')) {
            return;
        }

        $now = now();
        $known = DB::table('whatsapp_contacts')->pluck('normalized_phone')->all();
        $seen = array_fill_keys($known, true);

        $alerts = DB::table('alerts')
            ->where('channel', 'whatsapp')
            ->whereNotNull('contact_phone')
            ->select('contact_phone', 'user_id', 'guest_id')
            ->get();

        foreach ($alerts as $row) {
            $normalized = $this->normalizePhone($row->contact_phone);
            if (!$normalized || isset($seen[$normalized])) {
                continue;
            }

            DB::table('whatsapp_contacts')->insert([
                'normalized_phone' => $normalized,
                'raw_phone' => $row->contact_phone,
                'user_id' => $row->user_id,
                'guest_id' => $row->guest_id,
                'first_source' => 'currency-alert-backfill',
                'first_prompted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $seen[$normalized] = true;
        }

        $offerAlerts = DB::table('offer_alerts')
            ->where('channel', 'whatsapp')
            ->whereNotNull('contact_phone')
            ->select('contact_phone', 'user_id', 'guest_id')
            ->get();

        foreach ($offerAlerts as $row) {
            $normalized = $this->normalizePhone($row->contact_phone);
            if (!$normalized || isset($seen[$normalized])) {
                continue;
            }

            DB::table('whatsapp_contacts')->insert([
                'normalized_phone' => $normalized,
                'raw_phone' => $row->contact_phone,
                'user_id' => $row->user_id,
                'guest_id' => $row->guest_id,
                'first_source' => 'offer-alert-backfill',
                'first_prompted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $seen[$normalized] = true;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op to preserve collected contacts.
    }

    private function normalizePhone($phone): ?string
    {
        $value = trim((string) $phone);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value);
        $value = preg_replace('/[^\d\+]/', '', $value);

        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        if (!str_starts_with($value, '+')) {
            $value = '+' . $value;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') {
            return null;
        }

        return '+' . $digits;
    }
}
