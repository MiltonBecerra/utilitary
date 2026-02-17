<?php

namespace App\Modules\Utilities\OfferAlerts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfferAlert;
use App\Models\OfferPriceHistory;
use App\Models\Utility;
use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;
use App\Modules\Core\Services\GuestService;
use App\Modules\Core\Services\WhatsAppRegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OfferAlertController extends Controller
{
    protected OfferPriceScraperService $scraper;
    protected GuestService $guestService;
    protected WhatsAppRegistryService $whatsAppRegistryService;

    public function __construct(
        OfferPriceScraperService $scraper,
        GuestService $guestService,
        WhatsAppRegistryService $whatsAppRegistryService
    )
    {
        $this->scraper = $scraper;
        $this->guestService = $guestService;
        $this->whatsAppRegistryService = $whatsAppRegistryService;
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
        $canUseRecurring = in_array($plan, ['basic', 'pro'], true);
        $canUseRipley = $plan === 'pro';
        $pendingRecurringAlerts = $alerts
            ->filter(function (OfferAlert $alert) {
                return $alert->frequency === 'recurring'
                    && (bool) $alert->recurring_popup_pending
                    && in_array($alert->status, ['active', 'fallback_email'], true);
            })
            ->map(function (OfferAlert $alert) {
                return [
                    'id' => $alert->id,
                    'title' => $alert->title ?? 'Producto',
                    'store' => $alert->store,
                    'current_price' => $alert->current_price,
                    'target_price' => $alert->target_price,
                    'channel' => $alert->channel,
                    'contact_email' => $alert->contact_email,
                    'contact_phone' => $alert->contact_phone,
                    'last_notified_at' => $alert->last_notified_at?->format('d/m/Y H:i'),
                ];
            })
            ->values();
        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        return view('modules.offer_alerts.index', compact(
            'alerts',
            'stores',
            'utility',
            'comments',
            'plan',
            'canUseWhatsApp',
            'canUseRecurring',
            'canUseRipley',
            'lastPhone',
            'lastEmail',
            'pendingRecurringAlerts'
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
            'frequency' => 'required|in:once,recurring',
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

        if (($request->input('frequency') === 'recurring') && !in_array($plan, ['basic', 'pro'], true)) {
            return $this->errorResponse($request, 'Alertas recurrentes disponibles en planes Basic o Pro.');
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

        // Validación con fallback automático: mostrar precio disponible según lo que tenga el producto
        if ($priceType === 'cmr') {
            // Validación más flexible: null, false o 0 no son válidos
            $isValidCardPrice = $cmrPrice !== null && 
                               $cmrPrice !== false && 
                               is_numeric($cmrPrice) && 
                               $cmrPrice > 0;
                         
            if (!$isValidCardPrice) {
                // Si no hay precio CMR válido, usar automáticamente el precio público
                $selectedPrice = $publicPrice;
                $priceType = 'public'; // Cambiar el tipo para consistencia
                
                // Mantener cmr_price como null para que el frontend muestre "No disponible"
                $cmrPrice = null;
            } else {
                $selectedPrice = $cmrPrice;
            }
        } else {
            $selectedPrice = $publicPrice;
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
            'title' => isset($product['title']) ? html_entity_decode($product['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'store' => $product['store'] ?? $store,
            'image_url' => $product['image_url'] ?? null,
            'current_price' => $selectedPrice,
            'public_price' => $publicPrice,
            'cmr_price' => $cmrPrice,
            'price_type' => $priceType,
            'target_price' => $request->target_price,
            'notify_on_any_drop' => $request->boolean('notify_on_any_drop'),
            'frequency' => $request->input('frequency', 'once'),
            'status' => 'active',
            'recurring_popup_pending' => false,
        ]);

        OfferPriceHistory::create([
            'offer_alert_id' => $alert->id,
            'price' => $alert->current_price,
            'checked_at' => now(),
        ]);

        $registrationNotice = null;
        if ($channel === 'whatsapp' && !empty($alert->contact_phone)) {
            $result = $this->whatsAppRegistryService->registerIfFirst(
                $alert->contact_phone,
                $alert->user_id,
                $alert->guest_id,
                'offer-alert'
            );

            if (($result['is_first'] ?? false) && !empty($result['normalized_phone'])) {
                $companyNumber = $this->whatsAppRegistryService->getCompanyNumber();
                $registrationNotice = "Importante: para activar WhatsApp escribe primero al numero de la empresa {$companyNumber}.";
            }
        }

        $precioDetectado = $selectedPrice !== null ? number_format((float) $selectedPrice, 2) : null;
        $mensaje = 'Alerta creada.';
        if ($precioDetectado) {
            $mensaje .= " Precio detectado: S/ {$precioDetectado}.";
        }
        $with = ['success' => $mensaje];
        if ($fallbackNotice) {
            $with['warning'] = $fallbackNotice;
        }
        if ($registrationNotice) {
            $with['warning'] = trim(($with['warning'] ?? '') . ' ' . $registrationNotice);
        }

        if (Auth::check()) {
            return redirect()->route('offer-alerts.index')->with($with);
        }

        return redirect()->route('offer-alerts.index')->with($with);
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
            'frequency' => 'nullable|in:once,recurring',
            'channel' => 'nullable|in:email,whatsapp',
            'contact_email' => Auth::check()
                ? 'nullable|email'
                : 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
        ]);

        $contactEmail = Auth::check() ? Auth::user()->email : $request->contact_email;
        $contactPhone = $channel === 'whatsapp' ? $request->contact_phone : null;
        $frequency = $request->input('frequency', $offerAlert->frequency ?? 'once');

        if ($frequency === 'recurring' && !in_array($plan, ['basic', 'pro'], true)) {
            return $this->errorResponse($request, 'Alertas recurrentes disponibles en planes Basic o Pro.');
        }

        $nextStatus = $request->status ?? $offerAlert->status;

        $offerAlert->update([
            'target_price' => $request->target_price,
            'notify_on_any_drop' => $request->boolean('notify_on_any_drop'),
            'status' => $nextStatus,
            'channel' => $channel,
            'frequency' => $frequency,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'recurring_popup_pending' => in_array($nextStatus, ['active', 'fallback_email'], true)
                ? $offerAlert->recurring_popup_pending
                : false,
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

    public function deactivate(Request $request, OfferAlert $offerAlert)
    {
        if (Auth::check()) {
            abort_unless(Auth::id() === $offerAlert->user_id, 403);
        } else {
            $guestId = $this->guestService->getGuestId();
            abort_unless($offerAlert->guest_id === $guestId, 403);
        }

        $offerAlert->update([
            'status' => 'inactive',
            'recurring_popup_pending' => false,
        ]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Alerta desactivada.',
                'alert_id' => $offerAlert->id,
            ]);
        }

        return redirect()->route('offer-alerts.index')->with('success', 'Alerta desactivada.');
    }

    /**
     * Change price type from card to public via signed URL
     */
    public function changePriceType(Request $request, OfferAlert $offerAlert)
    {
        // Verify signed URL
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        // Verify ownership
        if (Auth::check()) {
            abort_unless(Auth::id() === $offerAlert->user_id, 403);
        } else {
            $guestId = $this->guestService->getGuestId();
            abort_unless($offerAlert->guest_id === $guestId, 403);
        }

        // Only allow changing from cmr to public
        if ($offerAlert->price_type !== 'cmr') {
            return back()->withErrors(['msg' => 'Esta alerta ya usa precio público.']);
        }

        // Update to public price type
        $offerAlert->update([
            'price_type' => 'public',
            'current_price' => $offerAlert->public_price, // Use public price as current
            'last_notified_price' => null, // Reset notification flag to allow new notifications
        ]);

        return redirect()->route('offer-alerts.index')
            ->with('success', 'Alerta actualizada a precio público correctamente.');
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withErrors(['msg' => $message]);
    }
}




