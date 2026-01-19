<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

interface StoreClientInterface
{
    public function storeCode(): string;

    public function storeName(): string;

    /**
     * Búsqueda amplia (fase 1).
     *
     * Retorna candidatos normalizados (array de arrays).
     */
    public function searchWide(string $query, string $location = ''): array;
}


