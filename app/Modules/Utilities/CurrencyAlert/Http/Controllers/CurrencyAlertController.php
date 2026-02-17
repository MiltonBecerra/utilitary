<?php

namespace App\Modules\Utilities\CurrencyAlert\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ExchangeSource;
use App\Models\ExchangeRate;
use App\Models\Utility;
use App\Modules\Core\Services\GuestService;
use App\Modules\Core\Services\WhatsAppRegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyAlertController extends Controller
{
    protected $guestService;
    protected WhatsAppRegistryService $whatsAppRegistryService;

    public function __construct(GuestService $guestService, WhatsAppRegistryService $whatsAppRegistryService)
    {
        $this->guestService = $guestService;
        $this->whatsAppRegistryService = $whatsAppRegistryService;
    }

    public function index()
    {
        $sources = ExchangeSource::where('is_active', true)->get();
        foreach ($sources as $source) {
            $source->latestRate = ExchangeRate::where('exchange_source_id', $source->id)
                ->latest()
                ->first();
        }

        $alerts = collect();
        $pendingRecurringAlerts = collect();
        $utility = Utility::where('slug', 'currency-alert')->first();

        $lastPhone = null;
        $lastEmail = null;
        if (Auth::check()) {
            $alerts = Alert::with('exchangeSource')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();
            $lastPhone = Alert::where('user_id', Auth::id())
                ->whereNotNull('contact_phone')
                ->orderByDesc('id')
                ->value('contact_phone');
            $lastEmail = Auth::user()->email ?: Alert::where('user_id', Auth::id())
                ->where('channel', 'email')
                ->whereNotNull('contact_detail')
                ->orderByDesc('id')
                ->value('contact_detail');
        } else {
            $guestId = $this->guestService->getGuestId();
            $alerts = Alert::with('exchangeSource')
                ->where('guest_id', $guestId)
                ->latest()
                ->get();
            $lastPhone = Alert::where('guest_id', $guestId)
                ->whereNotNull('contact_phone')
                ->orderByDesc('id')
                ->value('contact_phone');
            $lastEmail = Alert::where('guest_id', $guestId)
                ->where('channel', 'email')
                ->whereNotNull('contact_detail')
                ->orderByDesc('id')
                ->value('contact_detail');
        }

        $pendingRecurringAlerts = $alerts
            ->filter(function (Alert $alert) {
                return $alert->frequency === 'recurring'
                    && (bool) $alert->recurring_popup_pending
                    && in_array($alert->status, ['active', 'fallback_email'], true);
            })
            ->map(function (Alert $alert) {
                return [
                    'id' => $alert->id,
                    'exchange_source_name' => $alert->exchangeSource->name ?? 'Casa de cambio',
                    'target_price' => $alert->target_price,
                    'condition' => $alert->condition,
                    'channel' => $alert->channel,
                    'contact_detail' => $alert->contact_detail,
                    'contact_phone' => $alert->contact_phone,
                    'last_notified_at' => $alert->last_notified_at?->format('d/m/Y H:i'),
                ];
            })
            ->values();

        $comments = $utility ? $utility->comments()->latest()->get() : collect();

        return view('modules.currency_alert.index', compact('sources', 'alerts', 'utility', 'comments', 'lastPhone', 'lastEmail', 'pendingRecurringAlerts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'exchange_source_id' => 'required|exists:exchange_sources,id',
            'target_price' => 'required|numeric',
            'condition' => 'required|in:above,below',
            'notify_on_change' => 'nullable|boolean',
            'channel' => 'required|in:email,whatsapp',
            'contact_detail' => 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
            'frequency' => 'required|in:once,recurring',
        ]);

        $data = $request->all();
        $data['notify_on_change'] = $request->boolean('notify_on_change');
        if (($data['channel'] ?? '') === 'whatsapp') {
            $data['contact_detail'] = null;
        } else {
            $data['contact_phone'] = null;
        }

        if ($data['notify_on_change']) {
            $baselinePrice = $this->resolveLatestComparablePrice((int) $data['exchange_source_id'], (string) $data['condition']);
            $data['last_seen_price'] = $baselinePrice;
        } else {
            $data['last_seen_price'] = null;
        }

        $data['status'] = 'active';
        $utility = Utility::where('slug', 'currency-alert')->first();
        $utilityId = $utility?->id;

        if (Auth::check()) {
            $user = Auth::user();
            $data['user_id'] = $user->id;

            if ($user->hasReachedMonthlyAlertLimit($utilityId)) {
                $plan = $user->getActivePlan($utilityId);
                $limit = $user->getMonthlyAlertLimit($utilityId);
                return $this->errorResponse($request, "El plan {$plan} permite hasta {$limit} alertas por mes.");
            }

            if (!$user->canCreateAlert($utilityId)) {
                $plan = $user->getActivePlan($utilityId);
                $limit = $user->getAlertLimit($utilityId);
                $limitText = $limit === -1 ? 'ilimitadas' : $limit;
                return $this->errorResponse($request, "El plan {$plan} permite hasta {$limitText} alertas activas. Actualiza tu plan para más.");
            }

            if ($data['channel'] == 'whatsapp' && !$user->canUseWhatsApp($utilityId)) {
                return $this->errorResponse($request, 'WhatsApp solo está disponible en el plan Pro.');
            }

            if ($data['frequency'] == 'recurring' && !$user->canUseRecurringAlerts($utilityId)) {
                return $this->errorResponse($request, 'Alertas recurrentes solo disponibles en planes de pago.');
            }
        } else {
            $data['guest_id'] = $this->guestService->getGuestId();

            if (!$this->guestService->hasAcceptedTerms()) {
                return $this->errorResponse($request, 'Debes aceptar los términos y la política de privacidad para continuar.', 403);
            }

            if ($this->guestService->hasGuestReachedMonthlyAlertLimit($utilityId)) {
                $plan = $this->guestService->getGuestPlan($utilityId);
                $limit = $this->guestService->getGuestMonthlyAlertLimit($utilityId);
                return $this->errorResponse($request, "El plan {$plan} permite hasta {$limit} alertas por mes.");
            }

            if (!$this->guestService->canGuestCreateAlert($utilityId)) {
                $plan = $this->guestService->getGuestPlan($utilityId);
                $limit = $this->guestService->getGuestAlertLimit($utilityId);
                $limitText = $limit === -1 ? 'ilimitadas' : $limit;
                return $this->errorResponse($request, "El plan {$plan} permite hasta {$limitText} alertas activas. Actualiza tu plan para más.");
            }

            if ($data['channel'] == 'whatsapp' && !$this->guestService->canGuestUseWhatsApp($utilityId)) {
                return $this->errorResponse($request, 'WhatsApp solo disponible con Plan Pro. Actualiza tu plan.');
            }

            if ($data['frequency'] == 'recurring' && !$this->guestService->canGuestUseRecurringAlerts($utilityId)) {
                return $this->errorResponse($request, 'Alertas recurrentes disponibles con planes Basic o Pro. Actualiza tu plan.');
            }
        }

        $alert = Alert::create($data)->load('exchangeSource');

        $registrationNotice = null;
        if (($data['channel'] ?? '') === 'whatsapp' && !empty($data['contact_phone'])) {
            $result = $this->whatsAppRegistryService->registerIfFirst(
                $data['contact_phone'],
                $data['user_id'] ?? null,
                $data['guest_id'] ?? null,
                'currency-alert'
            );

            if (($result['is_first'] ?? false) && !empty($result['normalized_phone'])) {
                $registrationNotice = $this->buildWhatsappRegistrationNotice((string) $result['normalized_phone']);
            }
        }

        if ($request->expectsJson()) {
            $response = [
                'message' => 'Alerta creada exitosamente!',
                'alert' => $this->transformAlertForResponse($alert),
            ];

            if ($registrationNotice) {
                $response['whatsapp_registration_required'] = true;
                $response['whatsapp_company_number'] = $registrationNotice['company_number'];
                $response['whatsapp_registration_message'] = $registrationNotice['message'];
                $response['whatsapp_click_to_chat_url'] = $registrationNotice['click_to_chat_url'];
            }

            return response()->json($response, 201);
        }

        $redirect = redirect()->back()->with('success', 'Alerta creada exitosamente!');
        if ($registrationNotice) {
            $redirect->with('warning', $registrationNotice['message']);
        }

        return $redirect;
    }

    public function edit($id)
    {
        $alert = Alert::findOrFail($id);

        if (Auth::check()) {
            if ($alert->user_id !== Auth::id()) {
                return redirect()->back()->withErrors(['msg' => 'No tienes permiso para editar esta alerta.']);
            }
        } else {
            $guestId = $this->guestService->getGuestId();
            if ($alert->guest_id !== $guestId) {
                return redirect()->back()->withErrors(['msg' => 'No tienes permiso para editar esta alerta.']);
            }
        }

        $sources = ExchangeSource::where('is_active', true)->get();
        return view('modules.currency_alert.edit', compact('alert', 'sources'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'exchange_source_id' => 'required|exists:exchange_sources,id',
            'target_price' => 'required|numeric',
            'condition' => 'required|in:above,below',
            'notify_on_change' => 'nullable|boolean',
            'channel' => 'required|in:email,whatsapp',
            'contact_detail' => 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
            'frequency' => 'required|in:once,recurring',
        ]);

        $alert = Alert::findOrFail($id);
        $utility = Utility::where('slug', 'currency-alert')->first();
        $utilityId = $utility?->id;

        if (Auth::check()) {
            if ($alert->user_id !== Auth::id()) {
                return $this->errorResponse($request, 'No tienes permiso para editar esta alerta.');
            }

            $user = Auth::user();

            if ($request->channel == 'whatsapp' && !$user->canUseWhatsApp($utilityId)) {
                return $this->errorResponse($request, 'WhatsApp solo está disponible en el plan Pro.');
            }

            if ($request->frequency == 'recurring' && !$user->canUseRecurringAlerts($utilityId)) {
                return $this->errorResponse($request, 'Alertas recurrentes solo disponibles en planes de pago.');
            }
        } else {
            $guestId = $this->guestService->getGuestId();
            if ($alert->guest_id !== $guestId) {
                return $this->errorResponse($request, 'No tienes permiso para editar esta alerta.');
            }

            if ($request->channel == 'whatsapp' && !$this->guestService->canGuestUseWhatsApp($utilityId)) {
                return $this->errorResponse($request, 'WhatsApp solo disponible con Plan Pro. Actualiza tu plan.');
            }

            if ($request->frequency == 'recurring' && !$this->guestService->canGuestUseRecurringAlerts($utilityId)) {
                return $this->errorResponse($request, 'Alertas recurrentes disponibles con planes Basic o Pro. Actualiza tu plan.');
            }
        }

        $data = $request->all();
        $data['notify_on_change'] = $request->boolean('notify_on_change');
        if (($data['channel'] ?? '') === 'whatsapp') {
            $data['contact_detail'] = null;
        } else {
            $data['contact_phone'] = null;
        }

        $sourceChanged = (int) $alert->exchange_source_id !== (int) $data['exchange_source_id'];
        $conditionChanged = (string) $alert->condition !== (string) $data['condition'];
        $enabledChangeDetection = $data['notify_on_change'] && !$alert->notify_on_change;
        $missingBaseline = $data['notify_on_change'] && $alert->last_seen_price === null;

        if ($data['notify_on_change']) {
            if ($enabledChangeDetection || $sourceChanged || $conditionChanged || $missingBaseline) {
                $data['last_seen_price'] = $this->resolveLatestComparablePrice((int) $data['exchange_source_id'], (string) $data['condition']);
            } else {
                $data['last_seen_price'] = $alert->last_seen_price;
            }
        } else {
            $data['last_seen_price'] = null;
        }

        if ($alert->status === 'fallback_email' && $alert->channel === 'whatsapp' && $request->channel === 'email') {
            $data['status'] = 'active';
        }

        $alert->update($data);
        $alert->load('exchangeSource');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Alerta actualizada exitosamente!',
                'alert' => $this->transformAlertForResponse($alert),
            ]);
        }

        return redirect()->route('currency-alert.index')->with('success', 'Alerta actualizada exitosamente!');
    }

    public function destroy(Request $request, $id)
    {
        $alert = $this->findOwnedAlert($id);

        $alert->delete();

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Alerta eliminada exitosamente!']);
        }

        return redirect()->back()->with('success', 'Alerta eliminada exitosamente!');
    }

    public function deactivate(Request $request, $id)
    {
        $alert = $this->findOwnedAlert($id);

        $alert->update([
            'status' => 'inactive',
            'recurring_popup_pending' => false,
        ]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Alerta desactivada exitosamente!',
                'alert_id' => $alert->id,
            ]);
        }

        return redirect()->back()->with('success', 'Alerta desactivada exitosamente!');
    }

    private function transformAlertForResponse(Alert $alert): array
    {
        return [
            'id' => $alert->id,
            'exchange_source_id' => $alert->exchange_source_id,
            'exchange_source_name' => $alert->exchangeSource->name ?? '',
            'target_price' => $alert->target_price,
            'condition' => $alert->condition,
            'notify_on_change' => (bool) $alert->notify_on_change,
            'channel' => $alert->channel,
            'contact_detail' => $alert->contact_detail,
            'contact_phone' => $alert->contact_phone,
            'frequency' => $alert->frequency,
            'status' => $alert->status,
            'created_at' => $alert->created_at,
            'created_at_formatted' => $alert->created_at?->format('d/m/Y'),
            'created_at_ts' => $alert->created_at?->timestamp,
        ];
    }

    private function resolveLatestComparablePrice(int $exchangeSourceId, string $condition): ?float
    {
        $latestRate = ExchangeRate::where('exchange_source_id', $exchangeSourceId)
            ->latest()
            ->first();

        if (!$latestRate) {
            return null;
        }

        return $condition === 'below'
            ? (float) $latestRate->sell_price
            : (float) $latestRate->buy_price;
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withErrors(['msg' => $message]);
    }

    private function buildWhatsappRegistrationNotice(string $normalizedPhone): array
    {
        $companyNumber = $this->whatsAppRegistryService->getCompanyNumber();
        $message = $this->whatsAppRegistryService->getCompanyMessage($normalizedPhone);

        return [
            'company_number' => $companyNumber,
            'message' => "Importante: para activar WhatsApp escribe primero al numero de la empresa {$companyNumber}.",
            'click_to_chat_url' => $this->whatsAppRegistryService->getCompanyClickToChatUrl($message),
        ];
    }

    private function findOwnedAlert($id): Alert
    {
        $alert = Alert::findOrFail($id);

        if (Auth::check()) {
            if ((int) $alert->user_id !== (int) Auth::id()) {
                abort(403, 'No tienes permiso para acceder a esta alerta.');
            }

            return $alert;
        }

        $guestId = $this->guestService->getGuestId();
        if ((string) $alert->guest_id !== (string) $guestId) {
            abort(403, 'No tienes permiso para acceder a esta alerta.');
        }

        return $alert;
    }
}
