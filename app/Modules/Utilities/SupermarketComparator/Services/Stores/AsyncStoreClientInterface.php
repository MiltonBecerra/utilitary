<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

use GuzzleHttp\Promise\PromiseInterface;

interface AsyncStoreClientInterface extends StoreClientInterface
{
    /**
     * Busqueda amplia (fase 1) en modo async.
     *
     * Retorna una promesa que resuelve a candidatos normalizados (array de arrays).
     */
    public function searchWideAsync(string $query, string $location = ''): PromiseInterface;
}

