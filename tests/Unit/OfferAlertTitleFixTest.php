<?php

use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;
use App\Models\OfferAlert;

class OfferAlertTitleFixTest extends \PHPUnit\Framework\TestCase
{
    protected OfferPriceScraperService $scraper;

    protected function setUp(): void
    {
        // Crear instancia del servicio (sin usar app() de Laravel)
        $this->scraper = new OfferPriceScraperService();
    }

    /** @test */
    public function it_decodes_html_entities_in_json_ld()
    {
        // HTML con entidades HTML que vendría de JSON-LD
        $htmlWithEntities = '
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Product",
            "name": "&quot;Televisor Samsung 65\&quot; Crystal UHD Smart TV&quot;",
            "offers": {
                "@type": "Offer",
                "price": "1599.90"
            }
        }
        </script>
        ';

        $crawler = new Symfony\Component\DomCrawler\Crawler($htmlWithEntities);
        
        // Usar reflexión para acceder al método privado parseJsonLd
        $reflection = new ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseJsonLd');
        $method->setAccessible(true);
        
        $data = $method->invoke($this->scraper, $crawler);
        
        // Verificar que las entidades HTML fueron decodificadas y backslashes removidos
        $this->assertEquals('"Televisor Samsung 65" Crystal UHD Smart TV"', $data['name']);
        $this->assertStringNotContainsString('&quot;', $data['name']);
        $this->assertStringNotContainsString('\&quot;', $data['name']);
        $this->assertStringNotContainsString('\"', $data['name']);
    }

    /** @test */
    public function it_decodes_html_entities_in_text_extraction()
    {
        // HTML con entidades en un elemento h1
        $htmlWithEntities = '
        <h1>&quot;Laptop Gamer ASUS ROG&quot; - 16GB RAM</h1>
        ';

        $crawler = new Symfony\Component\DomCrawler\Crawler($htmlWithEntities);
        
        // Usar reflexión para acceder al método privado textFirst
        $reflection = new ReflectionClass($this->scraper);
        $method = $reflection->getMethod('textFirst');
        $method->setAccessible(true);
        
        $text = $method->invoke($this->scraper, $crawler, 'h1', 'Fallback');
        
        // Verificar que las entidades HTML fueron decodificadas
        $this->assertEquals('"Laptop Gamer ASUS ROG" - 16GB RAM', $text);
        $this->assertStringNotContainsString('&quot;', $text);
    }

    /** @test */
    public function it_handles_various_quote_escape_patterns()
    {
        $testCases = [
            '&quot;Simple quote&quot;' => '"Simple quote"',
            '&quot;Comillas con backslash\&quot; aqu&iacute;&quot;' => '"Comillas con backslash" aquí"',
            '&quot;Televisor 65\&quot; 4K&quot;' => '"Televisor 65" 4K"',
            'Normal &amp; text' => 'Normal & text',
            '&lt;script&gt;alert(1)&lt;/script&gt;' => '<script>alert(1)</script>',
        ];

        foreach ($testCases as $input => $expected) {
            $html = '
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "' . $input . '"
            }
            </script>
            ';

            $crawler = new Symfony\Component\DomCrawler\Crawler($html);
            $reflection = new ReflectionClass($this->scraper);
            $method = $reflection->getMethod('parseJsonLd');
            $method->setAccessible(true);
            
            $data = $method->invoke($this->scraper, $crawler);
            
            $this->assertEquals($expected, $data['name'], 
                "Failed for input: {$input}. Expected: {$expected}, Got: {$data['name']}");
        }
    }
}
