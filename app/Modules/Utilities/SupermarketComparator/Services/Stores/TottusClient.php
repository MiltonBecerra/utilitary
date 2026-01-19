<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use App\Traits\BrowserSimulationTrait;

class TottusClient implements StoreClientInterface
{
    use BrowserSimulationTrait;

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 12,
            'http_errors' => false,
            'verify' => false,
        ]);
    }

    public function storeCode(): string
    {
        return 'tottus';
    }

    public function storeName(): string
    {
        return 'Tottus';
    }

    public function searchWide(string $query, string $location = ''): array
    {
        \Log::info('tottus_debug', ['step' => 'enter_searchWide', 'query' => $query]);
        // Tottus (Next.js) expone resultados en __NEXT_DATA__.
        $baseUrl = rtrim(config('services.supermarket_comparator.tottus_base_url', 'https://www.tottus.com.pe'), '/');
        $searchUrl = $baseUrl . '/tottus-pe/buscar?Ntt=' . urlencode($query);
        
        $requestOptions = $this->getSimulatedOptions($searchUrl, 'tottus_search');
        
        // Tottus defaults
        $requestOptions['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $requestOptions['headers']['Accept-Encoding'] = 'identity';

        [$status, $html] = $this->getHtmlWithRetry($searchUrl, $requestOptions['headers'], $requestOptions);

        // Debug: guardar la última respuesta para inspección manual.
        if (config('app.debug') || env('SMC_DEBUG_SAVE_HTML')) {
            try {
                $pathHtml = storage_path('app/tottus_last_response.html');
                $pathMeta = storage_path('app/tottus_last_response_status.txt');
                @file_put_contents($pathHtml, $html);
                @file_put_contents($pathMeta, "status={$status}\nurl={$searchUrl}\nquery={$query}\n");
                \Log::info('tottus_debug_saved_html', ['path' => $pathHtml, 'status' => $status]);

                if ($status === 200) {
                    @file_put_contents(storage_path('app/tottus_last_success.html'), $html);
                }

                $dir = storage_path('app/tottus_responses');
                File::ensureDirectoryExists($dir);
                $ts = now()->format('Ymd_His');
                @file_put_contents($dir . DIRECTORY_SEPARATOR . "tottus_{$status}_{$ts}.html", $html);
            } catch (\Throwable $e) {
                \Log::warning('tottus_debug_save_failed', ['error' => $e->getMessage()]);
            }
        }

        if ($status !== 200) {
            $fallbackResults = $this->fetchWithPuppeteerResults($searchUrl);
            if (is_array($fallbackResults)) {
                return $this->buildItemsFromResults($fallbackResults);
            }
            $snippet = trim(substr(preg_replace('/\\s+/', ' ', $html) ?? $html, 0, 140));
            throw new \RuntimeException('Tottus: búsqueda no disponible (HTTP ' . $status . '). ' . ($snippet ? ('Respuesta: ' . $snippet) : ''));
        }

        if (!preg_match('/<script id=\"__NEXT_DATA__\" type=\"application\\/json\">(.*?)<\\/script>/s', $html, $m)) {
            $fallbackResults = $this->fetchWithPuppeteerResults($searchUrl);
            if (is_array($fallbackResults)) {
                return $this->buildItemsFromResults($fallbackResults);
            }
            throw new \RuntimeException('Tottus: no se encontró __NEXT_DATA__.');
        }

        $data = json_decode($m[1], true);
        if (!is_array($data)) {
            throw new \RuntimeException('Tottus: __NEXT_DATA__ inválido.');
        }

        $results = $data['props']['pageProps']['results'] ?? null;
        if (!is_array($results)) {
            throw new \RuntimeException('Tottus: respuesta inválida (sin resultados).');
        }

        return $this->buildItemsFromResults($results);
    }

    private function buildItemsFromResults(array $results): array
    {
        $items = [];
        foreach (array_slice($results, 0, 20) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = (string) ($row['displayName'] ?? '');
            if ($title === '') {
                continue;
            }

            $brand = $row['brand'] ?? null;
            $url = $row['url'] ?? null;
            $imageUrl = null;
            if (is_array($row['mediaUrls'] ?? null) && is_string($row['mediaUrls'][0] ?? null)) {
                $imageUrl = $row['mediaUrls'][0];
            }

            $price = null;
            $listPrice = null;
            $cardPrice = null;
            $cardLabel = null;
            $prices = $row['prices'] ?? null;
            if (is_array($prices)) {
                $cardCandidates = [];
                foreach ($prices as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $type = (string) ($p['type'] ?? '');
                    $value = $p['price'][0] ?? null;
                    $value = is_string($value) ? (float) str_replace(',', '.', $value) : (is_numeric($value) ? (float) $value : null);
                    if ($value === null) {
                        continue;
                    }
                    if ($type === 'internetPrice' && $price === null) {
                        $price = $value;
                    }
                    if ($type === 'normalPrice' && $listPrice === null) {
                        $listPrice = $value;
                    }

                    // Algunas integraciones (Falabella) exponen precios con tarjeta como tipos adicionales.
                    // Guardamos candidatos para elegir el más bajo.
                    $typeLower = strtolower($type);
                    if (str_contains($typeLower, 'cmr')) {
                        $cardCandidates[] = ['label' => 'CMR', 'price' => $value];
                    } elseif (str_contains($typeLower, 'oh')) {
                        $cardCandidates[] = ['label' => 'OH!', 'price' => $value];
                    } elseif (str_contains($typeLower, 'prime')) {
                        $cardCandidates[] = ['label' => 'Prime', 'price' => $value];
                    } elseif (str_contains($typeLower, 'card')) {
                        $cardCandidates[] = ['label' => 'Tarjeta', 'price' => $value];
                    }
                }

                if (!empty($cardCandidates)) {
                    usort($cardCandidates, fn ($a, $b) => ($a['price'] <=> $b['price']));
                    $cardPrice = (float) $cardCandidates[0]['price'];
                    $cardLabel = (string) $cardCandidates[0]['label'];
                }
            }

            $promoText = null;
            if ($listPrice !== null && $price !== null && $listPrice > $price) {
                $promoText = 'Antes S/ ' . number_format($listPrice, 2);
            }

            $items[] = [
                'title' => $title,
                'brand' => is_string($brand) ? $brand : null,
                'variant' => null,
                'audience' => null,
                'price' => $price,
                'card_price' => $cardPrice,
                'card_label' => $cardLabel,
                'promo_text' => $promoText,
                'in_stock' => true,
                'url' => is_string($url) ? $url : null,
                'image_url' => $imageUrl,
            ];
        }

        return $items;
    }

    private function fetchWithPuppeteerResults(string $url): ?array
    {
        $script = base_path('scrape_tottus_search.js');
        \Log::info('tottus_puppeteer_debug', ['script' => $script, 'exists' => file_exists($script)]);
        if (!file_exists($script)) {
            return null;
        }

        try {
            $nodePath = 'C:\Program Files\nodejs\node.exe';
            if (!file_exists($nodePath)) {
                $nodePath = 'node';
            }

            $profileBase = storage_path('logs/puppeteer_profiles_tottus');
            File::ensureDirectoryExists($profileBase);

            $env = [
                'PUPPETEER_PROFILE_BASE' => $profileBase,
                'PATH' => (getenv('PATH') ?: '') . ';C:\Windows\System32',
            ];

            $process = new Process([$nodePath, $script, $url], base_path(), $env);
            $process->setTimeout(45);
            $process->setIdleTimeout(45);
            $process->run();

            if (!$process->isSuccessful()) {
                \Log::error('tottus_puppeteer_failed', ['url' => $url, 'error' => $process->getErrorOutput(), 'output' => $process->getOutput()]);
                return null;
            }

            $output = trim($process->getOutput());
            $logSnippet = $output;
            if (strlen($logSnippet) > 1200) {
                $logSnippet = substr($logSnippet, 0, 1200) . '...';
            }
            \Log::info('tottus_puppeteer_output', ['snippet' => $logSnippet]);
            $data = json_decode($output, true);
            if (!is_array($data)) {
                \Log::error('tottus_puppeteer_invalid_json', ['output' => $output]);
                return null;
            }

            $results = $data['results'] ?? null;
            if (!is_array($results)) {
                \Log::warning('tottus_puppeteer_no_results', ['payload_keys' => array_keys($data)]);
                return null;
            }

            \Log::info('tottus_puppeteer_results', ['count' => count($results)]);
            return $results;
        } catch (\Throwable $e) {
            \Log::error('tottus_puppeteer_exception', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function getHtmlWithRetry(string $url, array $headers, array $options = []): array
    {
        $attempts = 5;
        $lastStatus = 0;
        $lastBody = '';
        for ($i = 1; $i <= $attempts; $i++) {
            [$status, $body] = $this->getHtmlViaGuzzle($url, $headers, $options);
            $lastStatus = $status;
            $lastBody = $body;

            // 503 "no healthy upstream" suele ser temporal en el edge; reintentar.
            if ($status === 503 && stripos($body, 'no healthy upstream') !== false) {
                usleep((int) (250000 * $i * $i)); // 0.25s, 1.0s, 2.25s, 4.0s...
                continue;
            }

            if ($status === 200) {
                return [$status, $body];
            }

            break;
        }

        // Si el navegador carga pero cURL/Guzzle falla, intentar con stream (a veces el WAF/edge discrimina por stack).
        if ($lastStatus !== 200) {
            [$status, $body] = $this->getHtmlViaStream($url, $headers);
            if ($status !== 0) {
                return [$status, $body];
            }
        }

        // Fallback Windows: PowerShell Invoke-WebRequest (a veces pasa cuando PHP/Guzzle recibe 503).
        if ($lastStatus !== 200 && PHP_OS_FAMILY === 'Windows') {
            for ($i = 1; $i <= 2; $i++) {
                [$status, $body] = $this->getHtmlViaPowerShell($url, $headers);
                if ($status !== 0) {
                    if ($status === 503 && stripos($body, 'no healthy upstream') !== false) {
                        usleep((int) (400000 * $i));
                        continue;
                    }
                    return [$status, $body];
                }
            }
        }

        return [$lastStatus, $lastBody];
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function getHtmlViaGuzzle(string $url, array $headers, array $options = []): array
    {
        $requestOptions = $options ?: ['headers' => $headers];
        try {
            $response = $this->client->get($url, $requestOptions);
        } catch (\Throwable $e) {
            return [0, $e->getMessage()];
        }

        return [$response->getStatusCode(), (string) $response->getBody()];
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function getHtmlViaStream(string $url, array $headers): array
    {
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headerLines),
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $body = $body === false ? '' : (string) $body;

        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/\\s(\\d{3})\\s/', (string) $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
        }

        return [$status, $body];
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function getHtmlViaPowerShell(string $url, array $headers): array
    {
        $psHeaders = [];
        foreach ($headers as $k => $v) {
            // Escapar comillas simples para PowerShell
            $vv = str_replace("'", "''", (string) $v);
            $kk = str_replace("'", "''", (string) $k);
            $psHeaders[] = "'{$kk}'='{$vv}'";
        }

        $u = str_replace("'", "''", $url);
        $headerHash = '@{' . implode(';', $psHeaders) . '}';

        $script = "\$u='{$u}';" .
            "\$h={$headerHash};" .
            "try{ " .
            "  \$r=Invoke-WebRequest -UseBasicParsing -Uri \$u -Headers \$h; " .
            "  \$o=@{status=[int]\$r.StatusCode; body=[string]\$r.Content} | ConvertTo-Json -Compress; " .
            "}catch{ " .
            "  \$resp=\$_.Exception.Response; " .
            "  \$status= if(\$resp -and \$resp.StatusCode){ [int]\$resp.StatusCode } else { 0 }; " .
            "  \$body=''; " .
            "  try{ if(\$resp){ \$sr=New-Object System.IO.StreamReader(\$resp.GetResponseStream()); \$body=\$sr.ReadToEnd(); \$sr.Close(); } }catch{} " .
            "  if(-not \$body){ \$body=[string]\$_.Exception.Message } " .
            "  \$o=@{status=\$status; body=\$body} | ConvertTo-Json -Compress; " .
            "};" .
            "Write-Output \$o;";

        $process = new Process(['powershell', '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-Command', $script]);
        $process->setTimeout(20);
        $process->run();

        if (!$process->isSuccessful()) {
            return [0, $process->getErrorOutput() ?: $process->getOutput()];
        }

        $output = trim($process->getOutput());
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            return [0, $output];
        }

        $status = isset($decoded['status']) ? (int) $decoded['status'] : 0;
        $body = isset($decoded['body']) ? (string) $decoded['body'] : '';
        return [$status, $body];
    }
}

