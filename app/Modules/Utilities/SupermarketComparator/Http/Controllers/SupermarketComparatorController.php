<?php

namespace App\Modules\Utilities\SupermarketComparator\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Utility;
use App\Models\SmcPurchase;
use App\Models\SmcPurchaseItem;
use App\Modules\Core\Services\GuestService;
use App\Modules\Utilities\SupermarketComparator\Services\BrandCatalog;
use App\Modules\Utilities\SupermarketComparator\Services\SupermarketComparatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SupermarketComparatorController extends Controller
{
    public function __construct(
        protected SupermarketComparatorService $comparator,
        protected GuestService $guestService,
    ) {
    }

    protected function utility(): ?Utility
    {
        try {
            return Utility::firstOrCreate(
                ['slug' => 'supermarket-comparator'],
                [
                    'name' => 'Comparador de Precios – Supermercados Perú',
                    'description' => 'Compara precios entre supermercados (Plaza Vea, Tottus, Metro, Wong) con equivalencias y precio unitario.',
                    'icon' => 'fas fa-shopping-basket',
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('supermarket_comparator_utility_load_failed', ['error' => $e->getMessage()]);
            return Utility::where('slug', 'supermarket-comparator')->first();
        }
    }

    public function index(Request $request)
    {
        $utility = $this->utility();
        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        $savedPurchases = $this->getSavedPurchases($request);
        $editingPurchase = $this->resolveEditPurchase($request);
        $editPurchase = $this->resolveEditPurchase($request);

        return view('modules.supermarket_comparator.index', [
            'utility' => $utility,
            'comments' => $comments,
            'savedPurchases' => $savedPurchases,
            'editingPurchase' => $editPurchase,
            'purchaseName' => $editPurchase?->name,
            'phase' => 'start',
            'contextToken' => null,
            'query' => $editPurchase?->query_text ?? '',
            'location' => $editPurchase?->location ?? '',
            'selectedStores' => is_array($editPurchase?->stores ?? null) ? $editPurchase->stores : null,
            'result' => null,
        ]);
    }

    public function brands(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:60',
            'limit' => 'nullable|integer|min:1|max:25',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);

        $items = [];
        if ($q !== '' && mb_strlen($q) >= 2) {
            $brands = (new BrandCatalog())->suggest($q, $limit);
            $items = array_map(fn ($b) => ['value' => $b], $brands);
        }

        return response()->json([
            'q' => $q,
            'items' => $items,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'queries' => 'nullable|string|max:2000',
            // backward compat si alguien manda "query"
            'query' => 'nullable|string|max:120',
            'stores' => 'required|array|min:1|max:10',
            'stores.*' => 'string|in:plaza_vea,tottus,metro,wong',
        ]);

        if (!Auth::check() && !$this->guestService->hasAcceptedTerms()) {
            $message = 'Debes aceptar los términos y la política de privacidad para continuar.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            return back()->withErrors(['msg' => $message])->withInput();
        }

        $queriesRaw = $validated['queries'] ?? ($validated['query'] ?? '');
        $queries = $this->parseQueries((string) $queriesRaw);
        if (empty($queries)) {
            return back()->withErrors(['msg' => 'Ingresa al menos 1 producto.'])->withInput();
        }

        $location = '';
        $stores = $validated['stores'] ?? null;

        $utility = $this->utility();
        $plan = $this->resolvePlan($utility);
        $this->enforceStoreLimits($stores, $plan);
        $this->enforceDailyProductLimit(count($queries), $plan, $utility);

        $results = [];
        foreach ($queries as $q) {
            $results[] = $this->comparator->phase1Search($q, $location, $stores);
        }

        // Si solo hay 1 producto y no es ambiguo, ejecutamos comparaciรณn automรกtica con refinamiento vacรญo
        // para evitar "pantalla en blanco" cuando no se requiere refinamiento.
        if (count($results) === 1 && !($results[0]['needs_refinement'] ?? true)) {
            $ctx = $results[0]['context_token'] ?? null;
            if (is_string($ctx) && $ctx !== '') {
                $suggested = is_array($results[0]['suggested_refinement'] ?? null) ? $results[0]['suggested_refinement'] : [];
                $results[0] = $this->comparator->phase2Compare($ctx, [
                    'brand' => isset($suggested['brand']) ? trim((string) $suggested['brand']) : null,
                    'size' => isset($suggested['size']) ? trim((string) $suggested['size']) : null,
                    'variant' => isset($suggested['variant']) ? trim((string) $suggested['variant']) : null,
                    'audience' => isset($suggested['audience']) ? trim((string) $suggested['audience']) : null,
                    'allow_similar' => true,
                ]);
                // Conservamos el token para el UI (editar refinamiento / re-comparar)
                $results[0]['context_token'] = $ctx;
            }
        }

        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        $savedPurchases = $this->getSavedPurchases($request);

        // Si solo hay 1 producto, mantenemos el comportamiento previo.
        $single = count($results) === 1 ? $results[0] : null;

        $viewData = [
            'utility' => $utility,
            'comments' => $comments,
            'savedPurchases' => $savedPurchases,
            'phase' => count($results) > 1 ? 'multi' : (($single && ($single['needs_refinement'] ?? false)) ? 'refine' : 'compare'),
            'contextToken' => $single['context_token'] ?? ($results[0]['context_token'] ?? null),
            'query' => implode("\n", $queries),
            'location' => $location,
            'result' => $single,
            'results' => $results,
            'selectedStores' => $stores,
            'editingPurchase' => $this->resolveEditPurchase($request),
        ];

        if ($request->expectsJson()) {
            $html = view('modules.supermarket_comparator.partials.results', $viewData)->render();
            return response()->json([
                'phase' => $viewData['phase'],
                'html' => $html,
            ]);
        }

        return view('modules.supermarket_comparator.index', $viewData);
    }

    public function retrySearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:2000',
            'context_token' => 'nullable|string|max:80',
            'stores' => 'required|array|min:1|max:10',
            'stores.*' => 'string|in:plaza_vea,tottus,metro,wong',
        ]);

        if (!Auth::check() && !$this->guestService->hasAcceptedTerms()) {
            $message = 'Debes aceptar los tÇ¸rminos y la polÇðtica de privacidad para continuar.';
            return response()->json(['message' => $message], 403);
        }

        $query = trim((string) $validated['query']);
        $stores = $validated['stores'] ?? null;
        $contextToken = trim((string) ($validated['context_token'] ?? ''));
        $location = '';

        $utility = $this->utility();
        $plan = $this->resolvePlan($utility);
        $this->enforceStoreLimits($stores, $plan);

        $result = $this->comparator->phase1Search($query, $location, $stores);
        if ($contextToken !== '') {
            $context = Cache::get($this->contextKey($contextToken));
            if (is_array($context)) {
                $mergedCandidates = is_array($context['candidates'] ?? null) ? $context['candidates'] : [];
                foreach (($result['candidates'] ?? []) as $storeCode => $items) {
                    $mergedCandidates[$storeCode] = $items;
                }

                $mergedErrors = is_array($context['errors'] ?? null) ? $context['errors'] : [];
                if (is_array($stores) && !empty($stores)) {
                    $storeNameMap = $this->storeNameMap();
                    foreach ($stores as $code) {
                        $code = strtolower((string) $code);
                        $name = $storeNameMap[$code] ?? null;
                        if ($name && array_key_exists($name, $mergedErrors)) {
                            unset($mergedErrors[$name]);
                        }
                    }
                }
                foreach (($result['errors'] ?? []) as $store => $msg) {
                    $mergedErrors[$store] = $msg;
                }

                $context['candidates'] = $mergedCandidates;
                $context['errors'] = $mergedErrors;
                Cache::put($this->contextKey($contextToken), $context, now()->addMinutes(30));

                $contextAmbiguity = is_array($context['ambiguity'] ?? null) ? $context['ambiguity'] : [];
                $result = [
                    'context_token' => $contextToken,
                    'query' => $context['query'] ?? $query,
                    'location' => $context['location'] ?? $location,
                    'suggested_refinement' => $context['suggested_refinement'] ?? ($result['suggested_refinement'] ?? []),
                    'ambiguity' => $contextAmbiguity ?: ($result['ambiguity'] ?? ['is_ambiguous' => false, 'reasons' => []]),
                    'needs_refinement' => (bool) ($contextAmbiguity['is_ambiguous'] ?? ($result['needs_refinement'] ?? false)),
                    'candidates' => $mergedCandidates,
                    'errors' => $mergedErrors,
                    'error_store_codes' => $this->errorStoreCodesFromErrors($mergedErrors),
                ];
            }
        }

        if ($contextToken !== '' && empty($result['context_token'])) {
            $result['context_token'] = $contextToken;
        }
        $editingPurchase = $this->resolveEditPurchase($request);

        $html = view('modules.supermarket_comparator.partials.result_card', [
            'result' => $result,
            'editingPurchase' => $editingPurchase,
        ])->render();

        return response()->json([
            'html' => $html,
        ]);
    }

    /**
     * @return string[]
     */
    private function parseQueries(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = array_map('trim', explode("\n", $raw));
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        // hard limit
        $lines = array_slice($lines, 0, 10);

        // min length filter
        $lines = array_values(array_filter($lines, fn ($l) => mb_strlen($l) >= 2));
        return $lines;
    }

    private function buildQueriesMetaFromText(string $raw, array $existingMeta): array
    {
        $lines = $this->parseQueries($raw);
        $byQuery = [];
        foreach ($existingMeta as $entry) {
            $q = trim((string) ($entry['query'] ?? ''));
            if ($q !== '' && !isset($byQuery[$q])) {
                $byQuery[$q] = $entry;
            }
        }

        $meta = [];
        foreach ($lines as $line) {
            if (isset($byQuery[$line])) {
                $meta[] = $byQuery[$line];
                continue;
            }
            $meta[] = [
                'id' => (string) Str::uuid(),
                'query' => $line,
                'refinement' => [],
            ];
        }

        return $meta;
    }

    private function resolveEditPurchase(Request $request): ?SmcPurchase
    {
        $uuid = trim((string) ($request->input('purchase_uuid')
            ?? $request->input('purchase')
            ?? $request->query('purchase', '')));
        if ($uuid === '') {
            return null;
        }
        return SmcPurchase::where('uuid', $uuid)->first();
    }

    public function compare(Request $request)
    {
        $validated = $request->validate([
            'context_token' => 'required|string',
            'brand' => 'nullable|string|max:60',
            'size' => 'nullable|string|max:40',
            'variant' => 'nullable|string|max:80',
            'audience' => 'nullable|string|max:60',
            'allow_similar' => 'nullable|boolean',
        ]);

        if (!Auth::check() && !$this->guestService->hasAcceptedTerms()) {
            $message = 'Debes aceptar los términos y la política de privacidad para continuar.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            return back()->withErrors(['msg' => $message])->withInput();
        }

        $contextToken = $validated['context_token'];

        $refinement = [
            'brand' => isset($validated['brand']) ? trim((string) $validated['brand']) : null,
            'size' => isset($validated['size']) ? trim((string) $validated['size']) : null,
            'variant' => isset($validated['variant']) ? trim((string) $validated['variant']) : null,
            'audience' => isset($validated['audience']) ? trim((string) $validated['audience']) : null,
            'allow_similar' => $request->boolean('allow_similar', true),
        ];

        $result = $this->comparator->phase2Compare($contextToken, $refinement);

        // Si el usuario ingresó marca manualmente, guardarla en el catálogo (sin BD)
        // para poder detectarla en futuras búsquedas/refinamientos.
        $brandManual = trim((string) ($refinement['brand'] ?? ''));
        if ($brandManual !== '') {
            try {
                (new BrandCatalog())->updateFromCandidates('user', [
                    ['brand' => $brandManual, 'title' => (string) ($result['query'] ?? '')],
                ]);
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        $utility = $this->utility();
        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        $savedPurchases = $this->getSavedPurchases($request);
        $editingPurchase = $this->resolveEditPurchase($request);

        $viewData = [
            'utility' => $utility,
            'comments' => $comments,
            'savedPurchases' => $savedPurchases,
            'phase' => 'compare',
            'contextToken' => $contextToken,
            'query' => $result['query'] ?? '',
            'location' => $result['location'] ?? '',
            'result' => $result,
            'results' => [],
            'editingPurchase' => $editingPurchase,
        ];

        if ($request->expectsJson()) {
            $html = view('modules.supermarket_comparator.partials.compare', [
                'result' => $result,
                'contextToken' => $contextToken,
                'editingPurchase' => $editingPurchase,
            ])->render();
            return response()->json([
                'phase' => $viewData['phase'],
                'context_token' => $contextToken,
                'html' => $html,
            ]);
        }

        return view('modules.supermarket_comparator.index', $viewData);
    }

    public function savePurchase(Request $request)
    {
        $validated = $request->validate([
            'queries' => 'nullable|string|max:2000',
            'name' => 'nullable|string|max:120',
            'stores' => 'nullable|array|min:1|max:10',
            'stores.*' => 'string|max:30',
            'queries_meta' => 'nullable|array|max:20',
            'queries_meta.*.id' => 'required_with:queries_meta|string|max:36',
            'queries_meta.*.query' => 'required_with:queries_meta|string|max:2000',
            'queries_meta.*.refinement.brand' => 'nullable|string|max:60',
            'queries_meta.*.refinement.size' => 'nullable|string|max:40',
            'queries_meta.*.refinement.variant' => 'nullable|string|max:80',
            'queries_meta.*.refinement.audience' => 'nullable|string|max:60',
            'queries_meta.*.refinement.allow_similar' => 'nullable|boolean',
            'items' => 'required|array|min:1|max:200',
            'items.*.store' => 'nullable|string|max:50',
            'items.*.store_label' => 'nullable|string|max:80',
            'items.*.title' => 'required|string|max:255',
            'items.*.url' => 'nullable|string|max:2000',
            'items.*.image_url' => 'nullable|string|max:2000',
            'items.*.query_id' => 'nullable|string|max:36',
            'items.*.quantity' => 'required|numeric|min:0.01|max:9999',
            'items.*.unit' => 'nullable|string|max:10',
            'items.*.price' => 'nullable|numeric|min:0|max:999999',
            'items.*.card_price' => 'nullable|numeric|min:0|max:999999',
        ]);

        $utility = $this->utility();
        $plan = $this->resolvePlan($utility);
        $this->enforceStoreLimits($validated['stores'] ?? null, $plan);

        $items = $validated['items'];
        $totals = $this->calculateTotals($items);

        $purchase = SmcPurchase::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'query_text' => $validated['queries'] ?? null,
            'queries' => $validated['queries_meta'] ?? null,
            'name' => $validated['name'] ?? null,
            'stores' => $validated['stores'] ?? null,
            'items_count' => count($items),
            'totals' => $totals,
        ]);

        foreach ($items as $item) {
            SmcPurchaseItem::create([
                'purchase_id' => $purchase->id,
                'query_id' => $item['query_id'] ?? null,
                'store' => $item['store'] ?? null,
                'store_label' => $item['store_label'] ?? null,
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'image_url' => $item['image_url'] ?? null,
                'quantity' => (float) $item['quantity'],
                'unit' => $item['unit'] ?? 'un',
                'price' => (float) ($item['price'] ?? 0),
                'card_price' => isset($item['card_price']) ? (float) $item['card_price'] : null,
            ]);
        }

        $this->rememberPurchase($request, $purchase->uuid);

        return response()->json([
            'purchase' => [
                'uuid' => $purchase->uuid,
                'label' => $purchase->label,
                'url' => route('supermarket-comparator.purchases.show', $purchase->uuid),
            ],
        ]);
    }

    public function showPurchase(string $purchase)
    {
        $purchaseModel = SmcPurchase::with('items')->where('uuid', $purchase)->firstOrFail();
        $totals = $purchaseModel->totals ?? $this->calculateTotals($purchaseModel->items->toArray());

        return view('modules.supermarket_comparator.purchase', [
            'purchase' => $purchaseModel,
            'items' => $purchaseModel->items,
            'totals' => $totals,
        ]);
    }

    public function editPurchase(string $purchase)
    {
        $purchaseModel = SmcPurchase::where('uuid', $purchase)->firstOrFail();
        $queries = $this->parseQueries((string) ($purchaseModel->query_text ?? ''));

        return view('modules.supermarket_comparator.purchase_edit', [
            'purchase' => $purchaseModel,
            'queries' => implode("\n", $queries),
            'stores' => is_array($purchaseModel->stores ?? null) ? $purchaseModel->stores : [],
        ]);
    }

    public function updatePurchase(Request $request, string $purchase)
    {
        $purchaseModel = SmcPurchase::where('uuid', $purchase)->firstOrFail();

        $validated = $request->validate([
            'queries' => 'nullable|string|max:2000',
            'name' => 'nullable|string|max:120',
            'stores' => 'nullable|array|min:1|max:10',
            'stores.*' => 'string|max:30',
        ]);

        $utility = $this->utility();
        $plan = $this->resolvePlan($utility);
        $this->enforceStoreLimits($validated['stores'] ?? null, $plan);

        $queryText = (string) ($validated['queries'] ?? '');
        $existingMeta = is_array($purchaseModel->queries ?? null) ? $purchaseModel->queries : [];
        $queriesMeta = $this->buildQueriesMetaFromText($queryText, $existingMeta);

        $purchaseModel->update([
            'query_text' => $queryText,
            'queries' => $queriesMeta,
            'name' => $validated['name'] ?? null,
            'stores' => $validated['stores'] ?? null,
        ]);

        return redirect()
            ->route('supermarket-comparator.purchases.show', $purchaseModel->uuid)
            ->with('status', 'Compra actualizada.');
    }

    public function deletePurchase(Request $request, string $purchase)
    {
        $purchaseModel = SmcPurchase::where('uuid', $purchase)->firstOrFail();
        $purchaseModel->delete();

        return redirect()
            ->route('supermarket-comparator.index')
            ->with('status', 'Compra eliminada.');
    }

    public function runPurchase(Request $request, string $purchase)
    {
        $purchaseModel = SmcPurchase::where('uuid', $purchase)->firstOrFail();
        $queries = is_array($purchaseModel->queries ?? null) ? $purchaseModel->queries : [];
        if (empty($queries)) {
            $lines = $this->parseQueries((string) ($purchaseModel->query_text ?? ''));
            $queries = array_map(fn ($q) => ['query' => $q, 'refinement' => []], $lines);
        }
        $location = $purchaseModel->location ?? '';
        $stores = is_array($purchaseModel->stores ?? null) ? $purchaseModel->stores : null;
        if (empty($stores)) {
            abort(422, 'Selecciona al menos un supermercado para comparar.');
        }

        $utility = $this->utility();
        $plan = $this->resolvePlan($utility);
        $this->enforceStoreLimits($stores, $plan);
        $this->enforceDailyProductLimit(count($queries), $plan, $utility);

        $results = [];
        foreach ($queries as $entry) {
            $queryText = trim((string) ($entry['query'] ?? ''));
            if ($queryText === '') {
                continue;
            }
            $phase1 = $this->comparator->phase1Search($queryText, $location, $stores);
            $ref = is_array($entry['refinement'] ?? null) ? $entry['refinement'] : [];
            $ctx = $phase1['context_token'] ?? null;
            if (is_string($ctx) && $ctx !== '' && !empty($ref)) {
                $phase1 = $this->comparator->phase2Compare($ctx, [
                    'brand' => isset($ref['brand']) ? trim((string) $ref['brand']) : null,
                    'size' => isset($ref['size']) ? trim((string) $ref['size']) : null,
                    'variant' => isset($ref['variant']) ? trim((string) $ref['variant']) : null,
                    'audience' => isset($ref['audience']) ? trim((string) $ref['audience']) : null,
                    'allow_similar' => isset($ref['allow_similar']) ? (bool) $ref['allow_similar'] : true,
                ]);
                $phase1['context_token'] = $ctx;
            }
            $results[] = $phase1;
        }

        $comments = $utility ? $utility->comments()->latest()->get() : collect();
        $savedPurchases = $this->getSavedPurchases($request);

        $single = count($results) === 1 ? $results[0] : null;
        $viewData = [
            'utility' => $utility,
            'comments' => $comments,
            'savedPurchases' => $savedPurchases,
            'phase' => count($results) > 1 ? 'multi' : (($single && ($single['needs_refinement'] ?? false)) ? 'refine' : 'compare'),
            'contextToken' => $single['context_token'] ?? ($results[0]['context_token'] ?? null),
            'query' => implode("\n", array_map(fn ($q) => (string) ($q['query'] ?? ''), $queries)),
            'location' => $location,
            'result' => $single,
            'results' => $results,
            'selectedStores' => $stores,
        ];

        return view('modules.supermarket_comparator.index', $viewData);
    }

    private function calculateTotals(array $items): array
    {
        $stores = [];
        $overall = ['normal' => 0.0, 'card' => 0.0];

        foreach ($items as $item) {
            $store = strtoupper((string) ($item['store'] ?? 'OTROS'));
            $storeLabel = strtoupper((string) ($item['store_label'] ?? $store));
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = (float) ($item['price'] ?? 0);
            $cardPrice = (float) ($item['card_price'] ?? 0);
            $cardValue = $cardPrice > 0 ? $cardPrice : $price;

            if (!isset($stores[$store])) {
                $stores[$store] = [
                    'label' => $storeLabel,
                    'normal' => 0.0,
                    'card' => 0.0,
                ];
            }

            $stores[$store]['normal'] += $price * $qty;
            $stores[$store]['card'] += $cardValue * $qty;
            $overall['normal'] += $price * $qty;
            $overall['card'] += $cardValue * $qty;
        }

        return [
            'stores' => array_values($stores),
            'overall' => $overall,
        ];
    }

    private function getSavedPurchases(Request $request)
    {
        if (auth()->check()) {
            return SmcPurchase::where('user_id', auth()->id())
                ->latest()
                ->limit(10)
                ->get();
        }

        $uuids = (array) $request->session()->get('smc_purchases', []);
        if (empty($uuids)) {
            return collect();
        }

        return SmcPurchase::whereIn('uuid', $uuids)
            ->latest()
            ->limit(10)
            ->get();
    }

    private function rememberPurchase(Request $request, string $uuid): void
    {
        if (auth()->check()) {
            return;
        }

        $uuids = (array) $request->session()->get('smc_purchases', []);
        array_unshift($uuids, $uuid);
        $uuids = array_values(array_unique($uuids));
        $uuids = array_slice($uuids, 0, 20);
        $request->session()->put('smc_purchases', $uuids);
    }

    private function resolvePlan(?Utility $utility): string
    {
        $utilityId = $utility?->id;
        if (Auth::check()) {
            $user = Auth::user();
            return $user ? $user->getActivePlan($utilityId) : 'free';
        }

        return $this->guestService->getGuestPlan($utilityId);
    }

    private function enforceStoreLimits(?array $stores, string $plan): void
    {
        if (!$stores) {
            return;
        }

        $stores = array_values(array_filter(array_map('strtolower', $stores)));
        $count = count($stores);

        $maxStores = match ($plan) {
            'free' => 2,
            'basic' => 3,
            'pro' => null,
            default => 2,
        };

        if ($maxStores !== null && $count > $maxStores) {
            abort(422, "Tu plan permite seleccionar hasta {$maxStores} supermercados.");
        }

        if ($plan !== 'pro' && in_array('tottus', $stores, true)) {
            abort(422, 'Tottus solo está disponible en el plan Pro.');
        }
    }

    private function enforceDailyProductLimit(int $count, string $plan, ?Utility $utility): void
    {
        if ($count <= 0) {
            return;
        }

        $limit = match ($plan) {
            'free' => 5,
            'basic' => 1000000,
            'pro' => 50,
            default => 5,
        };

        $key = $this->dailyUsageKey($utility?->slug ?? 'supermarket-comparator');
        $current = (int) Cache::get($key, 0);
        $remaining = max(0, $limit - $current);

        if ($count > $remaining) {
            abort(422, "Tu plan permite hasta {$limit} productos por día. Te quedan {$remaining} hoy.");
        }

        $ttl = now()->diffInSeconds(now()->endOfDay());
        Cache::put($key, $current + $count, $ttl > 0 ? $ttl : 86400);
    }

    private function dailyUsageKey(string $slug): string
    {
        $date = now()->format('Y-m-d');
        if (Auth::check()) {
            return "smc_daily_products:user:" . Auth::id() . ":{$slug}:{$date}";
        }

        $guestId = $this->guestService->getGuestId();
        return "smc_daily_products:guest:{$guestId}:{$slug}:{$date}";
    }

    private function contextKey(string $token): string
    {
        return 'smc:ctx:' . $token;
    }

    private function storeNameMap(): array
    {
        return [
            'plaza_vea' => 'Plaza Vea',
            'tottus' => 'Tottus',
            'metro' => 'Metro',
            'wong' => 'Wong',
        ];
    }

    private function errorStoreCodesFromErrors(array $errors): array
    {
        $nameToCode = array_flip($this->storeNameMap());
        $codes = [];
        foreach (array_keys($errors) as $storeName) {
            $code = $nameToCode[$storeName] ?? null;
            if ($code) {
                $codes[] = $code;
            }
        }
        return array_values(array_unique($codes));
    }
}




