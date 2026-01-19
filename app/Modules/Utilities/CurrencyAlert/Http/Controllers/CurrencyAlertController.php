<?php

namespace App\Modules\Utilities\CurrencyAlert\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ExchangeSource;
use App\Models\ExchangeRate;
use App\Models\Utility;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyAlertController extends Controller
{
    protected $guestService;

    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
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
        $utility = Utility::where('slug', 'currency-alert')->first();

        $lastPhone = null;
        $lastEmail = null;
        if (Auth::check()) {
            $alerts = Alert::where('user_id', Auth::id())->latest()->get();
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
            $alerts = Alert::where('guest_id', $guestId)->latest()->get();
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
        $comments = $utility ? $utility->comments()->latest()->get() : collect();

        return view('modules.currency_alert.index', compact('sources', 'alerts', 'utility', 'comments', 'lastPhone', 'lastEmail'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'exchange_source_id' => 'required|exists:exchange_sources,id',
            'target_price' => 'required|numeric',
            'condition' => 'required|in:above,below',
            'channel' => 'required|in:email,whatsapp',
            'contact_detail' => 'exclude_unless:channel,email|required_if:channel,email|email',
            'contact_phone' => 'exclude_unless:channel,whatsapp|required_if:channel,whatsapp|nullable|string',
            'frequency' => 'required|in:once,recurring',
        ]);

        $data = $request->all();
        if (($data['channel'] ?? '') === 'whatsapp') {
            $data['contact_detail'] = null;
        } else {
            $data['contact_phone'] = null;
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Alerta creada exitosamente!',
                'alert' => $this->transformAlertForResponse($alert),
            ], 201);
        }

        return redirect()->back()->with('success', 'Alerta creada exitosamente!');
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
        if (($data['channel'] ?? '') === 'whatsapp') {
            $data['contact_detail'] = null;
        } else {
            $data['contact_phone'] = null;
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
        $alert = Alert::findOrFail($id);

        if (Auth::check()) {
            if ($alert->user_id !== Auth::id()) {
                return $this->errorResponse($request, 'No tienes permiso para eliminar esta alerta.');
            }
        } else {
            $guestId = $this->guestService->getGuestId();
            if ($alert->guest_id !== $guestId) {
                return $this->errorResponse($request, 'No tienes permiso para eliminar esta alerta.');
            }
        }

        $alert->delete();

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Alerta eliminada exitosamente!']);
        }

        return redirect()->back()->with('success', 'Alerta eliminada exitosamente!');
    }

    private function transformAlertForResponse(Alert $alert): array
    {
        return [
            'id' => $alert->id,
            'exchange_source_id' => $alert->exchange_source_id,
            'exchange_source_name' => $alert->exchangeSource->name ?? '',
            'target_price' => $alert->target_price,
            'condition' => $alert->condition,
            'channel' => $alert->channel,
            'contact_detail' => $alert->contact_detail,
            'contact_phone' => $alert->contact_phone,
            'frequency' => $alert->frequency,
            'status' => $alert->status,
            'created_at' => $alert->created_at,
            'created_at_formatted' => $alert->created_at?->format('d/m/Y'),
        ];
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withErrors(['msg' => $message]);
    }
}



