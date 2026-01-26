const express = require('express');
const puppeteer = require('puppeteer');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || 'utilitary-secret-key-2024';

app.use(cors());
app.use(express.json());

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Ripley scraping endpoint
app.post('/scrape/ripley', async (req, res) => {
  const { url, searchParams = [] } = req.body;
  
  // Validate API key
  const providedKey = req.headers['x-api-key'];
  if (providedKey !== API_KEY) {
    return res.status(401).json({ 
      success: false, 
      error: 'Invalid API key' 
    });
  }

  if (!url) {
    return res.status(400).json({ 
      success: false, 
      error: 'URL is required' 
    });
  }

  let browser;
  let page;

  try {
    console.log(`Starting Puppeteer scrape for: ${url}`);
    
    // Launch browser with optimized settings
    browser = await puppeteer.launch({
      headless: 'new',
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--disable-gpu',
        '--window-size=1920,1080',
        '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
      ]
    });

    page = await browser.newPage();
    
    // Set additional headers
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'es-PE,es;q=0.9,en;q=0.8',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
      'Accept-Encoding': 'gzip, deflate, br',
      'Cache-Control': 'no-cache',
      'Pragma': 'no-cache'
    });

    // Go to URL with timeout
    await page.goto(url, { 
      waitUntil: 'networkidle2', 
      timeout: 30000 
    });

    // Wait for critical selectors
    try {
      await Promise.race([
        page.waitForSelector('.product-internet-price, .catalog-prices__offer-price, .product-price__current', { timeout: 5000 }),
        page.waitForSelector('[data-testid="product-price"], [itemprop="price"], meta[property="product:price:amount"]', { timeout: 5000 })
      ]);
    } catch (e) {
      console.log('Selectors not found within timeout, proceeding anyway');
    }

    // Extract product data
    const products = await page.evaluate(() => {
      const extractPrice = (text) => {
        if (!text) return null;
        const clean = text.replace(/[^0-9,\.]/g, '');
        if (!clean) return null;
        
        // Handle Peruvian format
        if (clean.includes(',') && clean.includes('.')) {
          const parts = clean.split(',');
          if (parts.length === 2 && parts[1].length === 2) {
            return parseFloat(clean.replace(/\./g, '').replace(',', '.'));
          }
          return parseFloat(clean.replace(/\,/g, ''));
        } else if (clean.includes(',')) {
          const parts = clean.split(',');
          if (parts.length === 2 && parts[1].length === 2) {
            return parseFloat(clean.replace(',', '.'));
          }
          return parseFloat(clean.replace(',', ''));
        }
        return parseFloat(clean);
      };

      // Try to extract title
      const title = document.title || 
                   document.querySelector('h1')?.innerText?.trim() || 
                   'Producto';

      // Try to extract image
      const image = document.querySelector('meta[property="og:image"]')?.content ||
                    document.querySelector('meta[name="twitter:image"]')?.content ||
                    document.querySelector('img[itemprop="image"]')?.src ||
                    '';

      // Public price selectors
      const publicSelectors = [
        '.product-internet-price .product-price',
        '.catalog-prices__offer-price',
        '.product-price__current',
        '.price__main',
        '.product-price__final-price',
        '.product-prices__price',
        '[itemprop="price"]',
        'meta[property="product:price:amount"]',
        '.price-current',
        '.sale-price',
        '.internet-price'
      ];

      // Card price selectors
      const cardSelectors = [
        '.product-ripley-price .product-price',
        '.catalog-prices__card-price',
        '.product-price__card',
        '.price-card',
        '.tarjeta-ripley-price',
        '.product-price__card-price',
        '.product-prices__card-price',
        '[data-testid="card-price"]',
        '.ripley-card-price',
        '.tarjeta-precio'
      ];

      let publicPrice = null;
      let cardPrice = null;

      // Extract public price
      for (const selector of publicSelectors) {
        try {
          const element = document.querySelector(selector);
          if (element) {
            const text = element.textContent || element.content || element.getAttribute('content');
            const price = extractPrice(text);
            if (price && price > 0) {
              publicPrice = price;
              break;
            }
          }
        } catch (e) {
          continue;
        }
      }

      // Extract card price
      for (const selector of cardSelectors) {
        try {
          const element = document.querySelector(selector);
          if (element) {
            const text = element.textContent || element.content;
            const price = extractPrice(text);
            if (price && price > 0) {
              cardPrice = price;
              break;
            }
          }
        } catch (e) {
          continue;
        }
      }

      // Extract from JSON-LD if no prices found
      if (!publicPrice || !cardPrice) {
        try {
          const jsonLdScripts = document.querySelectorAll('script[type="application/ld+json"]');
          jsonLdScripts.forEach(script => {
            try {
              const data = JSON.parse(script.textContent);
              if (data && typeof data === 'object') {
                let offers = data.offers;
                if (!offers && Array.isArray(data)) {
                  offers = data.find(item => item.offers)?.offers;
                }
                
                if (offers) {
                  if (Array.isArray(offers)) {
                    offers.forEach(offer => {
                      if (offer.price && !publicPrice) {
                        publicPrice = extractPrice(String(offer.price));
                      }
                      if (offer.priceSpecification && Array.isArray(offer.priceSpecification)) {
                        offer.priceSpecification.forEach(spec => {
                          if (spec.price && !cardPrice && 
                              (spec.name || '').toLowerCase().includes('tarjeta')) {
                            cardPrice = extractPrice(String(spec.price));
                          }
                        });
                      }
                    });
                  } else if (offers.price) {
                    if (!publicPrice) {
                      publicPrice = extractPrice(String(offers.price));
                    }
                    if (offers.priceSpecification && Array.isArray(offers.priceSpecification)) {
                      offers.priceSpecification.forEach(spec => {
                        if (spec.price && !cardPrice && 
                            (spec.name || '').toLowerCase().includes('tarjeta')) {
                          cardPrice = extractPrice(String(spec.price));
                        }
                      });
                    }
                  }
                }
              }
            } catch (e) {
              // Continue to next script
            }
          });
        } catch (e) {
          // JSON-LD extraction failed
        }
      }

      return [{
        name: title,
        price: publicPrice || cardPrice,
        image: image,
        link: window.location.href,
        store: 'ripley',
        public_price: publicPrice,
        card_price: cardPrice
      }];
    });

    console.log(`Successfully extracted: ${products.length} products`);
    
    res.json({ 
      success: true, 
      data: products,
      url: url,
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error('Puppeteer scraping error:', error);
    
    res.status(500).json({ 
      success: false, 
      error: error.message,
      url: url,
      timestamp: new Date().toISOString()
    });
  } finally {
    // Clean up
    if (page) {
      try {
        await page.close();
      } catch (e) {
        console.error('Error closing page:', e);
      }
    }
    if (browser) {
      try {
        await browser.close();
      } catch (e) {
        console.error('Error closing browser:', e);
      }
    }
  }
});

// Start server
app.listen(PORT, () => {
  console.log(`Puppeteer server running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
  console.log(`Ripley endpoint: POST http://localhost:${PORT}/scrape/ripley`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM received, shutting down gracefully');
  process.exit(0);
});

process.on('SIGINT', async () => {
  console.log('SIGINT received, shutting down gracefully');
  process.exit(0);
});