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
        
        // Esperar y extraer resultados
        await page.waitForSelector('.product-item', { timeout: 10000 });
        
        const products = await page.evaluate(() => {
            const items = document.querySelectorAll('.product-item');
            return Array.from(items).map(item => ({
                name: item.querySelector('.product-name')?.textContent?.trim(),
                price: item.querySelector('.product-price')?.textContent?.trim(),
                image: item.querySelector('img')?.src,
                link: item.querySelector('a')?.href
            }));
        });
        
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