<?php

namespace App\Modules\Utilities\OfferAlerts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfferAlert;
use App\Models\OfferPriceHistory;
use App\Models\Utility;
use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OfferAlertController extends Controller
{
    protected OfferPriceScraperService $scraper;
    protected GuestService $guestService;

    public function __construct(OfferPriceScraperService $scraper, GuestService $guestService)
    {
        $this->scraper = $scraper;
        $this->guestService = $guestService;
    }

    protected function utility(): ?Utility
    {
        try {
            return Utility::firstOrCreate(
                ['slug' => 'offer-alert'],
                [
                    'name' => 'Alertas de ofertas',
                    'description' => 'Monitorea precios en ecommerce y recibe alertas cuando bajen.',
                    'icon' => 'fas fa-tag',
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('offer_alert_utility_load_failed', ['error' => $e->getMessage()]);
            return Utility::where('slug', 'offer-alert')->first();
        }
    }

    public function create()
    {
        return redirect()->route('offer-alerts.index');
    }

    public function index()
    {
        if (Auth::check()) {
            $alerts = OfferAlert::where('user_id', Auth::id())->latest()->get();
        } else {
            $guestId = $this->guestService->getGuestId();
            $alerts = OfferAlert::where('guest_id', $guestId)->latest()->get();
        }
        $lastAlert = $alerts->first();
        $lastPhone = $lastAlert?->contact_phone;
        $lastEmail = Auth::check()
            ? Auth::user()->email
            : ($lastAlert?->contact_email);
        $stores = ['falabella','ripley','oechsle','sodimac','promart'];
        $utility = $this->utility();
        $utilityId = $utility?->id;
        $plan = Auth::check() ? Auth::user()->getActivePlan($utilityId) : $this->guestService->getGuestPlan($utilityId);
        $canUseWhatsApp = $plan === 'pro';
        $canUseRipley = $plan === 'pro';
        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        return view('modules.offer_alerts.index', compact(
            'alerts',
            'stores',
            'utility',
            'comments',
            'plan',
            'canUseWhatsApp',
            'canUseRipley',
            'lastPhone',
            'lastEmail'
        ));
    }

    public function store(Request $request)
    {
        $utility = $this->utility();
        $utilityId = $utility?->id;
        $plan = Auth::check() ? Auth::user()->getActivePlan($utilityId) : $this->guestService->getGuestPlan($utilityId);

        $channel = $request->input('channel', 'email');
        if ($plan !== 'pro') {
            $channel = 'email';
        }
        $request->merge(['channel' => $channel]);

        $request->validate([
            'url' => 'required|url',
            'target_price' => 'nullable|numeric',
            'notify_on_any_drop' => 'sometimes|boolean',
            'price_type' => 'required|in:public,cmr',
            'channel' => 'nullable|in:email,whatsapp',
            'contact_email' => Auth::check()
                ? 'nullable|email'
                : 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
        ]);

        if (!Auth::check() && !$this->guestService->hasAcceptedTerms()) {
            return back()
                ->withErrors(['msg' => 'Debes aceptar los términos y la política de privacidad para continuar.'])
                ->withInput();
        }

        $store = $this->scraper->detectStore($request->url);
        $fallbackNotice = null;

        // Reglas de plan
        if ($plan === 'free') {
            $limitError = $this->getFreeLimitError($store);
            if ($limitError) {
                return $this->errorResponse($request, $limitError);
            }
        } elseif ($plan === 'basic') {
            $limitError = $this->getActiveLimitError(5);
            if ($limitError) {
                return $this->errorResponse($request, $limitError);
            }
        } elseif ($plan === 'pro') {
            $limitError = $this->getActiveLimitError(15);
            if ($limitError) {
                return $this->errorResponse($request, $limitError);
            }
        }
        if ($store === 'ripley' && $plan !== 'pro') {
            return back()->withErrors(['msg' => 'Ripley no estรก disponible en este plan.'])->withInput();
        }

        $channel = $request->input('channel', 'email');
        if ($plan !== 'pro') {
            $channel = 'email';
        }
        if ($channel === 'whatsapp') {
            $phone = Auth::check() ? $request->contact_phone : $request->contact_phone;
            if (!$phone) {
                return back()->withErrors(['msg' => 'Para WhatsApp ingresa un teléfono.'])->withInput();
            }
        }

        $product = $this->scraper->fetchProduct($request->url);
        if (!isset($product['price']) || $product['price'] === null) {
            if ($request->filled('target_price')) {
                $product['price'] = (float) $request->target_price;
                $product['public_price'] = $product['public_price'] ?? $product['price'];
                $fallbackNotice = 'No se pudo obtener el precio actual; se usó el precio objetivo como referencia.';
            } else {
                return back()
                    ->withErrors(['msg' => 'No se pudo obtener el precio actual del producto. Revisa storage/app/scrape-offer.html y storage/app/scrape-offer-debug.log.'])
                    ->withInput();
            }
        }

        $publicPrice = $product['public_price'] ?? $product['price'];
        $cmrPrice = $product['cmr_price'] ?? null;
        $priceType = $request->price_type;
        $selectedPrice = $priceType === 'cmr' ? $cmrPrice : $publicPrice;

        // Si el precio no público no existe, usar el precio público (fallback global).
        if ($priceType === 'cmr' && ($selectedPrice === null || $selectedPrice === false)) {
            $selectedPrice = $publicPrice;
            $priceType = 'public';
            $cardName = match ($store) {
                'ripley' => 'Tarjeta Ripley',
                'oechsle' => 'Tarjeta Oh',
                'sodimac' => 'Única/CMR',
                'promart' => 'Tarjeta Oh',
                default => 'CMR',
            };
            $fallbackNotice = "No se encontró precio {$cardName}; se usó precio público.";
        }

        $alert = OfferAlert::create([
            'user_id' => Auth::id(),
            'guest_id' => Auth::check() ? null : $this->guestService->getGuestId(),
            'utility_id' => $utilityId,
            'public_token' => Auth::check() ? null : Str::uuid(),
            'contact_email' => Auth::check() ? Auth::user()->email : $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'channel' => $channel,
            'url' => $request->url,
            'title' => $product['title'] ?? null,
            'store' => $product['store'] ?? $store,
            'image_url' => $product['image_url'] ?? null,
            'current_price' => $selectedPrice,
            'public_price' => $publicPrice,
            'cmr_price' => $cmrPrice,
            'price_type' => $priceType,
            'target_price' => $request->target_price,
            'notify_on_any_drop' => $request->boolean('notify_on_any_drop'),
            'status' => 'active',
        ]);

        OfferPriceHistory::create([
            'offer_alert_id' => $alert->id,
            'price' => $alert->current_price,
            'checked_at' => now(),
        ]);

        $precioDetectado = $selectedPrice !== null ? number_format((float) $selectedPrice, 2) : null;
        $mensaje = 'Alerta creada.';
        if ($precioDetectado) {
            $mensaje .= " Precio detectado: S/ {$precioDetectado}.";
        }
        $with = ['success' => $mensaje];
        if ($fallbackNotice) {
            $with['warning'] = $fallbackNotice;
        }

        if (Auth::check()) {
            return redirect()->route('offer-alerts.index')->with($with);
        }

        return redirect()->route('offer-alerts.public.show', $alert->public_token)->with($with);
    }

    protected function getFreeLimitError(string $store): ?string
    {
        $allowedStatuses = ['active', 'fallback_email'];

        if (Auth::check()) {
            $userId = Auth::id();
            $count = OfferAlert::where('user_id', $userId)->whereIn('status', $allowedStatuses)->count();
            if ($count >= 2) {
                return 'Plan Free permite 2 alertas activas.';
            }

            $distinctStores = OfferAlert::where('user_id', $userId)
                ->whereIn('status', $allowedStatuses)
                ->distinct()
                ->pluck('store');
            if ($distinctStores->isNotEmpty() && !$distinctStores->contains($store)) {
                return 'Plan Free permite solo 1 tienda.';
            }
        } else {
            $guestId = $this->guestService->getGuestId();
            $count = OfferAlert::where('guest_id', $guestId)->whereIn('status', $allowedStatuses)->count();
            if ($count >= 2) {
                return 'Plan Free permite 2 alertas activas.';
            }

            $distinctStores = OfferAlert::where('guest_id', $guestId)
                ->whereIn('status', $allowedStatuses)
                ->distinct()
                ->pluck('store');
            if ($distinctStores->isNotEmpty() && !$distinctStores->contains($store)) {
                return 'Plan Free permite solo 1 tienda.';
            }
        }

        return null;
    }

    protected function getActiveLimitError(int $limit): ?string
    {
        $allowedStatuses = ['active', 'fallback_email'];

        if (Auth::check()) {
            $count = OfferAlert::where('user_id', Auth::id())
                ->whereIn('status', $allowedStatuses)
                ->count();
        } else {
            $guestId = $this->guestService->getGuestId();
            $count = OfferAlert::where('guest_id', $guestId)
                ->whereIn('status', $allowedStatuses)
                ->count();
        }

        if ($count >= $limit) {
            return "Este plan permite hasta {$limit} alertas activas.";
        }

        return null;
    }

    public function show(OfferAlert $offerAlert)
    {
        abort_unless(Auth::id() === $offerAlert->user_id, 403);
        $offerAlert->load('priceHistories');
        return view('modules.offer_alerts.show', compact('offerAlert'));
    }

    public function showPublic(string $token)
    {
        $offerAlert = OfferAlert::where('public_token', $token)->firstOrFail();
        $offerAlert->load('priceHistories');
        return view('modules.offer_alerts.show', compact('offerAlert'));
    }

    public function update(Request $request, OfferAlert $offerAlert)
    {
        if (Auth::check()) {
            abort_unless(Auth::id() === $offerAlert->user_id, 403);
        } else {
            $guestId = $this->guestService->getGuestId();
            abort_unless($offerAlert->guest_id === $guestId, 403);
        }

        $utility = $this->utility();
        $utilityId = $utility?->id;
        $plan = Auth::check()
            ? Auth::user()->getActivePlan($utilityId)
            : $this->guestService->getGuestPlan($utilityId);

        $channel = $request->input('channel', $offerAlert->channel ?? 'email');
        if ($plan !== 'pro') {
            $channel = 'email';
        }
        $request->merge(['channel' => $channel]);

        $request->validate([
            'target_price' => 'nullable|numeric',
            'notify_on_any_drop' => 'sometimes|boolean',
            'status' => 'nullable|in:active,inactive,triggered,fallback_email',
            'channel' => 'nullable|in:email,whatsapp',
            'contact_email' => Auth::check()
                ? 'nullable|email'
                : 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
        ]);

        $contactEmail = Auth::check() ? Auth::user()->email : $request->contact_email;
        $contactPhone = $channel === 'whatsapp' ? $request->contact_phone : null;

        $offerAlert->update([
            'target_price' => $request->target_price,
            'notify_on_any_drop' => $request->boolean('notify_on_any_drop'),
            'status' => $request->status ?? $offerAlert->status,
            'channel' => $channel,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
        ]);

        return back()->with('success', 'Alerta actualizada.');
    }

    public function destroy(Request $request, OfferAlert $offerAlert)
    {
        if (Auth::check()) {
            abort_unless(Auth::id() === $offerAlert->user_id, 403);
        } else {
            $guestId = $this->guestService->getGuestId();
            abort_unless($offerAlert->guest_id === $guestId, 403);
        }
        $offerAlert->delete();

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Alerta eliminada.']);
        }

        return redirect()->route('offer-alerts.index')->with('success', 'Alerta eliminada.');
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withErrors(['msg' => $message]);
    }
}




