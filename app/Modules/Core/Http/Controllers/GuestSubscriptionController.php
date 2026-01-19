<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Subscription;
use App\Models\GuestEmail;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GuestSubscriptionController extends Controller
{
    protected $guestService;

    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
    }

    /**
     * Show the upgrade plan form for guests.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade()
    {
        $currentPlan = $this->guestService->getGuestPlan();
        $guestId = $this->guestService->getGuestId();

        // Get guest email if exists
        $guestEmail = GuestEmail::where('guest_id', $guestId)->first();
        $email = $guestEmail ? $guestEmail->email : '';

        return view('guest.upgrade', compact('currentPlan', 'email'));
    }

    /**
     * Process the plan upgrade for guest.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processUpgrade(Request $request)
    {
        $request->validate([
            'plan_type' => 'required|in:basic,pro',
            'duration_months' => 'required|integer|min:1|max:12',
            'email' => 'required|email',
        ]);

        Log::warning('Guest upgrade blocked: payment processing disabled.');
        return back()->withErrors(['msg' => 'El procesamiento de pagos esta deshabilitado.'])->withInput();
    }

    /**
     * Display the guest's current subscription.
     *
     * @return \Illuminate\Http\Response
     */
    public function mySubscription()
    {
        $guestId = $this->guestService->getGuestId();
        $currentSubscription = $this->guestService->getGuestSubscription();
        $subscriptionHistory = Subscription::forGuest($guestId)
            ->orderBy('created_at', 'desc')
            ->get();

        $guestEmail = GuestEmail::where('guest_id', $guestId)->first();
        $email = $guestEmail ? $guestEmail->email : null;

        return view('guest.my-subscription', compact('currentSubscription', 'subscriptionHistory', 'email'));
    }

    /**
     * Send confirmation email to guest.
     *
     * @param string $email
     * @param string $planType
     * @param \Carbon\Carbon $endsAt
     * @return void
     */
    protected function sendConfirmationEmail($email, $planType, $endsAt)
    {
        try {
            Mail::raw(
                "雁Gracias por tu compra!\n\n" .
                "Plan: " . strtoupper($planType) . "\n" .
                "Vケlido hasta: " . $endsAt->format('d/m/Y') . "\n\n" .
                "Puedes ver tus alertas en cualquier momento visitando nuestro sitio.\n\n" .
                "Nota: Tus datos estケn guardados en este navegador. Te recomendamos registrarte para acceder desde cualquier dispositivo.",
                function ($message) use ($email, $planType) {
                    $message->to($email)
                        ->subject('ConfirmaciИn de SuscripciИn - Plan ' . strtoupper($planType));
                }
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send guest confirmation email: ' . $e->getMessage());
        }
    }
}



