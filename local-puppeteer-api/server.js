const express = require('express');
const puppeteer = require('puppeteer');
const cors = require('cors');
const app = express();

// Middleware de autenticación simple
const API_KEY = process.env.API_KEY || 'utilitary-secret-key-2024';

const authMiddleware = (req, res, next) => {
    const providedKey = req.headers['x-api-key'] || req.query.api_key;
    if (providedKey !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    next();
};

app.use(cors());
app.use(express.json());

const PORT = 3001;

// Endpoint para scraping de Ripley
app.post('/scrape/ripley', authMiddleware, async (req, res) => {
    let browser;
    try {
        const { url, searchParams } = req.body;
        
        browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        
        const page = await browser.newPage();
        await page.goto(url, { waitUntil: 'networkidle2' });
        
        // Esperar y extraer productos - adaptado para páginas de producto de Ripley
        let products = [];
        
        try {
            // Intentar esperar a productos específicos de Ripley
            await page.waitForSelector('.product-page', '.product-internet-price', '[data-testid="product-price"]', { timeout: 5000 });
            
            products = await page.evaluate(() => {
                // Para páginas de producto individual
                const title = document.querySelector('h1')?.textContent?.trim() || 'Producto Ripley';
                const price = document.querySelector('.product-internet-price .product-price, .product-price__current, [data-testid="product-price"]')?.textContent?.trim();
                const image = document.querySelector('meta[property="og:image"]')?.content || document.querySelector('img[itemprop="image"]')?.src;
                
                return [{
                    name: title,
                    price: price,
                    image: image,
                    link: url
                }];
            });
        } catch (e) {
            // Fallback para cualquier página - extraer información básica del producto
            products = await page.evaluate(() => {
                const title = document.querySelector('h1')?.textContent?.trim() || document.title;
                const priceElement = document.querySelector('[data-testid="product-price"], .product-price, .price, meta[property="product:price:amount"]');
                let price = null;
                
                if (priceElement) {
                    if (priceElement.tagName === 'META') {
                        price = priceElement.content;
                    } else {
                        price = priceElement.textContent?.trim();
                    }
                }
                
                const image = document.querySelector('meta[property="og:image"]')?.content || document.querySelector('img')?.src;
                
                return [{
                    name: title,
                    price: price,
                    image: image,
                    link: window.location.href
                }];
            });
        }
        
        res.json({ success: true, data: products });
        
    } catch (error) {
        console.error('Error en scraping:', error);
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    } finally {
        if (browser) await browser.close();
    }
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`API Puppeteer corriendo en puerto ${PORT}`);
});