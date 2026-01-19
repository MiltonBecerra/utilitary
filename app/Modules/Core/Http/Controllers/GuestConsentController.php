<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GuestConsent;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;

class GuestConsentController extends Controller
{
    public function store(Request $request, GuestService $guestService)
    {
        $guestId = $guestService->getGuestId();

        GuestConsent::updateOrCreate(
            ['guest_id' => $guestId],
            [
                'accepted_at' => now(),
                'accepted_ip' => $request->ip(),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
