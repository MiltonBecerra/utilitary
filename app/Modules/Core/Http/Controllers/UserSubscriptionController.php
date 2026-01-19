<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Flash;

class UserSubscriptionController extends Controller
{
    /**
     * Display the user's current subscription.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $currentSubscription = $user->activeSubscription()->first();
        $subscriptionHistory = $user->subscriptions()->orderBy('created_at', 'desc')->get();

        return view('user.my-subscription', compact('currentSubscription', 'subscriptionHistory'));
    }

    /**
     * Show the upgrade plan form.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade()
    {
        if (!Auth::check()) {
            return redirect()->route('guest.subscription.upgrade');
        }

        $user = Auth::user();
        $currentPlan = $user->getActivePlan();

        return view('user.upgrade', compact('currentPlan'));
    }

    /**
     * Process the plan upgrade.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processUpgrade(Request $request)
    {
        $request->validate([
            'plan_type' => 'required|in:basic,pro',
            'duration_months' => 'required|integer|min:1|max:12',
        ]);

        Flash::error('El procesamiento de pagos esta deshabilitado.');
        return back()->withInput();
    }
}


