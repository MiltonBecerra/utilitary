const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const path = require('path');
const fs = require('fs');
const os = require('os');
const { randomUUID } = require('crypto');

puppeteer.use(StealthPlugin());

const url = process.argv[2];

if (!url) {
    console.error('Please provide a URL');
    process.exit(1);
}

const createdUserDataDirs = [];

const cleanupDir = (dir) => {
    if (!dir) return;
    try {
        fs.rmSync(dir, { recursive: true, force: true });
    } catch (e) { }
};

const resolveProfileBase = () => {
    if (process.env.PUPPETEER_PROFILE_BASE) {
        return path.resolve(process.env.PUPPETEER_PROFILE_BASE);
    }
    return path.join(os.tmpdir(), 'puppeteer_profiles');
};

const createTempProfile = (baseDir) => {
    fs.mkdirSync(baseDir, { recursive: true });
    const dir = fs.mkdtempSync(path.join(baseDir, `profile_${randomUUID()}_`));
    createdUserDataDirs.push(dir);
    return dir;
};

const launchWithFreshProfile = async (baseDir) => {
    let lastError = null;
    for (let attempt = 0; attempt < 2; attempt++) {
        const userDataDir = createTempProfile(baseDir);
        try {
            const browserInstance = await puppeteer.launch({
                headless: "new",
                args: ['--no-sandbox', '--disable-setuid-sandbox'],
                userDataDir
            });
            return { browserInstance, userDataDir };
        } catch (err) {
            lastError = err;
            cleanupDir(userDataDir);
            // Si hay bloqueo por un perfil colgado, reintenta con otro
            if (!String(err.message || '').includes('already running')) {
                break;
            }
        }
    }
    throw lastError;
};

(async () => {
    let browser = null;
    let userDataDir = null;
    const profileBase = resolveProfileBase();
    const NAV_TIMEOUT = 30000; // 30s to avoid hitting PHP max_execution_time
    try {
        const launchResult = await launchWithFreshProfile(profileBase);
        browser = launchResult.browserInstance;
        userDataDir = launchResult.userDataDir;

        const page = await browser.newPage();
        page.setDefaultNavigationTimeout(NAV_TIMEOUT);
        page.setDefaultTimeout(NAV_TIMEOUT);

        // Set a realistic viewport
        await page.setViewport({ width: 1920, height: 1080 });

        // console.log(`Navigating to ${url}...`);

        // Go to URL and wait for DOMContentLoaded
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT });

        // Detect HTTP 403 early and abort
        if (response && response.status() === 403) {
            console.error(`HTTP 403 for ${url}`);
            await browser.close();
            browser = null;
            cleanupDir(userDataDir);
            process.exit(1);
        }

        // Check for Cloudflare block
        const bodyText = await page.evaluate(() => document.body.innerText);
        if (bodyText.includes('Access Denied') || bodyText.includes('403 Forbidden')) {
            console.error('Blocked by Cloudflare (Access Denied)');
            await browser.close();
            browser = null;
            // Try to clean up temp dir
            cleanupDir(userDataDir);
            process.exit(1);
        }

        // Evaluate page content to extract data
        const productData = await page.evaluate(() => {
            const getText = (selector) => {
                const el = document.querySelector(selector);
                return el ? el.innerText.trim() : null;
            };

            const getAttr = (selector, attr) => {
                const el = document.querySelector(selector);
                return el ? el.getAttribute(attr) : null;
            };

const toNumber = (raw) => {
                if (raw == null) return null;
                const clean = String(raw).replace(/[^0-9,\\.]/g, '');
                if (!clean) return null;
                let normalized = clean;
                
                // Si hay ambos separadores, asumir formato peruano: 1,399 = 1399
                if (normalized.includes('.') && normalized.includes(',')) {
                    // Contar cuántos hay de cada uno
                    const dots = (normalized.match(/\./g) || []).length;
                    const commas = (normalized.match(/,/g) || []).length;
                    
                    if (commas === 1 && dots > 0) {
                        // Formato: 1.399,00 o similar - coma es decimal
                        normalized = normalized.replace(/\./g, '').replace(',', '.');
                    } else if (dots === 1 && commas > 1) {
                        // Formato: 1,399.00 - punto es decimal
                        normalized = normalized.replace(/,/g, '');
                    } else {
                        // Asumir formato peruano: 1,399 = 1399 (coma es miles)
                        normalized = normalized.replace(',', '');
                    }
                } else if (normalized.includes(',') && !normalized.includes('.')) {
                    // Solo coma - verificar si es decimal o miles
                    if (normalized.split(',')[1] && normalized.split(',')[1].length === 2) {
                        // Probablemente es decimal (ej: 1499,90)
                        normalized = normalized.replace(',', '.');
                    } else {
                        // Probablemente es miles (ej: 1,399)
                        normalized = normalized.replace(',', '');
                    }
                }
                
                const n = Number(normalized);
                return Number.isFinite(n) ? n : null;
            };

            const firstPriceFromSelectors = (selectors) => {
                for (const sel of selectors) {
                    const t = getText(sel);
                    const n = toNumber(t);
                    if (n !== null) return n;
                }
                return null;
            };

// Attempt to find LD+JSON first
            let data = {};
            let foundPrices = { public: [], card: [] };
            const scripts = document.querySelectorAll('script[type="application/ld+json"]');
            for (let script of scripts) {
                try {
                    const json = JSON.parse(script.innerText);
                    if (json['@type'] === 'Product' || json['@type'] === 'ProductGroup') {
                        data = json;
                        
                        // Extraer precios de múltiples ofertas
                        if (json.offers) {
                            // Priorizar sale_price (precio internet) sobre price (precio normal)
                            if (json.offers.sale_price) {
                                foundPrices.public.push(toNumber(json.offers.sale_price));
                            } else if (json.offers.price) {
                                foundPrices.public.push(toNumber(json.offers.price));
                            }
                            
                            // Si es array de ofertas
                            if (Array.isArray(json.offers)) {
                                json.offers.forEach(offer => {
                                    if (offer.price) {
                                        // Detectar si es precio de tarjeta
                                        const description = (offer.description || '').toLowerCase();
                                        const name = (offer.name || '').toLowerCase();
                                        if (description.includes('tarjeta') || description.includes('ripley') || 
                                            name.includes('tarjeta') || name.includes('ripley')) {
                                            foundPrices.card.push(toNumber(offer.price));
                                        } else {
                                            foundPrices.public.push(toNumber(offer.price));
                                        }
                                    }
                                });
                            }
                            
                            // Extraer de priceSpecification
                            if (json.offers.priceSpecification && Array.isArray(json.offers.priceSpecification)) {
                                json.offers.priceSpecification.forEach(spec => {
                                    if (spec.price) {
                                        const specName = (spec.name || '').toLowerCase();
                                        if (specName.includes('tarjeta') || specName.includes('ripley')) {
                                            foundPrices.card.push(toNumber(spec.price));
                                        } else {
                                            foundPrices.public.push(toNumber(spec.price));
                                        }
                                    }
                                });
                            }
                        }
                        break;
                    }
                    if (Array.isArray(json) && json[0]['@type'] === 'Product') {
                        data = json[0];
                        break;
                    }
                } catch (e) { }
            }

// Selectores actualizados para precio público (precio Internet)
            const publicPriceSelectors = [
                '.product-internet-price-not-best .product-price',
                '.catalog-prices__offer-price',
                '.product-price__current',
                '.price__main',
                '.product-price__final-price',
                '.product-prices__price',
                '.fbra_text--product-price',
                '[itemprop="price"]',
                'meta[property="product:price:amount"]'
            ];

            // Selectores actualizados para precio de tarjeta Ripley
            const cardPriceSelectors = [
                '.product-ripley-price .product-price',
                '.catalog-prices__card-price',
                '.product-price__card',
                '.price-card',
                '.tarjeta-ripley-price',
                '.product-price__card-price',
                '.product-prices__card-price',
                '[data-testid="card-price"]'
            ];

            const bodyText = document.body ? document.body.innerText : '';

let publicPrice = null;
            let cardPrice = null;
            
            // Priorizar precios encontrados en JSON-LD
            if (foundPrices.public.length > 0) {
                publicPrice = Math.min(...foundPrices.public);
            }
            if (foundPrices.card.length > 0) {
                cardPrice = Math.min(...foundPrices.card);
            }
            
            // Fallback a selectores CSS
            if (publicPrice === null) {
                publicPrice = firstPriceFromSelectors(publicPriceSelectors);
            }
            if (cardPrice === null) {
                cardPrice = firstPriceFromSelectors(cardPriceSelectors);
            }
            if (cardPrice === null && bodyText) {
                // Regex mejorada para evitar capturar puntos o bonos
                const regexes = [
                    // Patrones específicos de precio de tarjeta (más estrictos)
                    /(?:Precio\s+)?(?:con\s+)?(?:Tarjeta|TC\.?|T\.?C\.?)\s+(?:Banco\s+)?Ripley[\s\S]{0,80}?(?:S\/\s*)?([0-9]{1,6}[.,]?[0-9]{0,2})/i,
                    /Tarjeta\s+(?:Ripley|Banco\s+Ripley)[\s\S]{0,50}?(?:S\/\s*)?([0-9]{1,6}[.,]?[0-9]{0,2})/i,
                ];
                
                // Patrones a excluir (puntos, bonos, etc.)
                const exclusionPatterns = [
                    /puntos/i,
                    /acumulas/i,
                    /RipleyPuntos/i,
                    /go$/i,
                    /bono/i,
                    /descuento.*%/i,
                ];
                
                for (const re of regexes) {
                    const m = bodyText.match(re);
                    if (m && m[1]) {
                        // Verificar que el match no contenga patrones de exclusión
                        const fullMatch = m[0];
                        let hasExclusion = false;
                        for (const exclRe of exclusionPatterns) {
                            if (exclRe.test(fullMatch)) {
                                hasExclusion = true;
                                break;
                            }
                        }
                        
                        if (!hasExclusion) {
                            const n = toNumber(m[1]);
                            if (n !== null && n > 0 && n < 99999) {
                                cardPrice = n;
                                break;
                            }
                        }
                    }
                }
            }

            const title = data.name || getText('h1') || getText('.product-header__title');
            const image = data.image || getAttr('meta[property="og:image"]', 'content');

            return {
                title,
                price: Math.min(...[publicPrice, cardPrice].filter(p => p !== null)),
                public_price: publicPrice,
                card_price: cardPrice || publicPrice,
                image,
                url: window.location.href
            };
        });

        console.log(JSON.stringify(productData, null, 2));
    } catch (error) {
        console.error('Error:', error.message);
        process.exitCode = 1;
    } finally {
        try { if (browser) await browser.close(); } catch (e) { }
        // limpiar perfiles temporales generados
        const uniqueDirs = Array.from(new Set(createdUserDataDirs));
        uniqueDirs.forEach(cleanupDir);
    }
})();
