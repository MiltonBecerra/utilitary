<?php

namespace App\Modules\SunatRxH\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class SunatRxHService
{
    protected $scriptPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/sunat_scraper.js');
    }

    public function emitirRxH(array $data)
    {
        // Path to Node.js executable. In XAMPP/Windows it might be just 'node' if in PATH.
        // Since 'where node' failed for the user, we try default installation paths.
        $nodeBin = 'C:\\Program Files\\nodejs\\node.exe'; 
        if (!file_exists($nodeBin)) {
            // Fallback to locally installed node (if any) or trying just 'node' again.
            $nodeBin = 'node';
        } 

        $payload = json_encode($data);

        // Escape the payload for the command line is tricky on Windows.
        // Best approach is to pass it as a single argument or write to a temp file.
        // Let's try passing as argument first, but Base64 encoding avoids quote issues.
        $payloadBase64 = base64_encode($payload);

        $process = new Process([$nodeBin, $this->scriptPath, $payloadBase64]);
        $process->setWorkingDirectory(base_path());
        
        // Log the manual command for the user
        Log::info("MANUAL EXECUTION COMMAND:\n" . $nodeBin . ' ' . $this->scriptPath . ' ' . $payloadBase64);

        // Increase timeout for scraper (Sunat is slow)
        $process->setTimeout(120); 

        try {
            $process->mustRun();

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Scraper JSON Parse Error: " . json_last_error_msg());
                Log::error("Scraper Raw Output: " . $output);
                return ['success' => false, 'error' => 'Invalid JSON output from scraper'];
            }

            return $result;

        } catch (ProcessFailedException $exception) {
            Log::error("Scraper Process Failed: " . $exception->getMessage());
            return ['success' => false, 'error' => 'Scraper execution failed'];
        }
    }
}
