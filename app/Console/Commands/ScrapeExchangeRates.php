<?php

namespace App\Console\Commands;

use App\Modules\Core\Services\ScrapingService;
use Illuminate\Console\Command;

class ScrapeExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape exchange rates from configured sources';

    protected $scrapingService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ScrapingService $scrapingService)
    {
        parent::__construct();
        $this->scrapingService = $scrapingService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting scraping...');
        $this->scrapingService->scrapeAll();
        $this->info('Scraping completed.');

        $this->info('Dispatching CheckAlertsJob...');
        \App\Jobs\CheckAlertsJob::dispatch();
        
        return 0;
    }
}
