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
            if (!String(err.message || '').includes('already running')) {
                break;
            }
        }
    }
    throw lastError;
};

const toNumber = (raw) => {
    if (raw == null) return null;
    const clean = String(raw).replace(/[^0-9,\\.]/g, '');
    if (!clean) return null;
    let normalized = clean;
    
    if (normalized.includes('.') && normalized.includes(',')) {
        const dots = (normalized.match(/\./g) || []).length;
        const commas = (normalized.match(/,/g) || []).length;
        
        if (commas === 1 && dots > 0) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else if (dots === 1 && commas > 1) {
            normalized = normalized.replace(/,/g, '');
        } else {
            normalized = normalized.replace(',', '');
        }
    } else if (normalized.includes(',') && !normalized.includes('.')) {
        if (normalized.split(',')[1] && normalized.split(',')[1].length === 2) {
            normalized = normalized.replace(',', '.');
        } else {
            normalized = normalized.replace(',', '');
        }
    }
    
    const n = Number(normalized);
    return Number.isFinite(n) ? n : null;
};

(async () => {
    let browser = null;
    let userDataDir = null;
    const profileBase = resolveProfileBase();
    const NAV_TIMEOUT = 30000;
    
    try {
        const launchResult = await launchWithFreshProfile(profileBase);
        browser = launchResult.browserInstance;
        userDataDir = launchResult.userDataDir;

        const page = await browser.newPage();
        page.setDefaultNavigationTimeout(NAV_TIMEOUT);
        page.setDefaultTimeout(NAV_TIMEOUT);
        await page.setViewport({ width: 1920, height: 1080 });

        console.log(`\n🔍 ANALIZANDO URL: ${url}`);
        console.log("=" * 80);

        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT });

        if (response && response.status() === 403) {
            console.error(`❌ HTTP 403 para ${url}`);
            await browser.close();
            process.exit(1);
        }

        const bodyText = await page.evaluate(() => document.body.innerText);
        if (bodyText.includes('Access Denied') || bodyText.includes('403 Forbidden')) {
            console.error('❌ Bloqueado por Cloudflare (Access Denied)');
            await browser.close();
            process.exit(1);
        }

        const debugData = await page.evaluate(() => {
            const getText = (selector) => {
                const el = document.querySelector(selector);
                return el ? el.innerText.trim() : null;
            };

            const getAttr = (selector, attr) => {
                const el = document.querySelector(selector);
                return el ? el.getAttribute(attr) : null;
            };

            const getElementDetails = (selector) => {
                const elements = document.querySelectorAll(selector);
                const details = [];
                elements.forEach((el, index) => {
                    const rect = el.getBoundingClientRect();
                    details.push({
                        index,
                        selector,
                        text: el.innerText.trim(),
                        content: el.getAttribute('content') || '',
                        display: window.getComputedStyle(el).display,
                        visibility: window.getComputedStyle(el).visibility,
                        opacity: window.getComputedStyle(el).opacity,
                        position: {
                            x: rect.x,
                            y: rect.y,
                            width: rect.width,
                            height: rect.height
                        },
                        classes: el.className,
                        id: el.id
                    });
                });
                return details;
            };

            // Analizar JSON-LD
            let jsonLdData = [];
            const scripts = document.querySelectorAll('script[type="application/ld+json"]');
            scripts.forEach((script, index) => {
                try {
                    const json = JSON.parse(script.innerText);
                    jsonLdData.push({
                        index,
                        type: json['@type'] || 'unknown',
                        name: json.name || 'no-name',
                        offers: json.offers || null,
                        fullData: json
                    });
                } catch (e) {
                    jsonLdData.push({
                        index,
                        error: e.message,
                        rawText: script.innerText.substring(0, 200)
                    });
                }
            });

            // Selectores de precios públicos
            const publicSelectors = [
                '.product-internet-price-not-best .product-price',
                '.catalog-prices__offer-price',
                '.product-price__current',
                '.price__main',
                '.product-price__final-price',
                '.product-prices__price',
                '[data-testid="product-price"]',
                '[data-test-id="product-price"]',
                '[data-test-id="internet-price"]',
                '[itemprop="price"]',
                'meta[property="product:price:amount"]',
                'meta[name="twitter:data1"]',
                '.price-current',
                '.sale-price',
                '.internet-price',
                '.product-internet-price',
                '.best-price',
                '.current-price',
            ];

            // Selectores de precios de tarjeta
            const cardSelectors = [
                '.product-ripley-price .product-price',
                '.catalog-prices__card-price',
                '.product-price__card',
                '.price-card',
                '.tarjeta-ripley-price',
                '.product-price__card-price',
                '.product-prices__card-price',
                '[data-testid="card-price"]',
                '[data-test-id="card-price"]',
                '[data-test-id="ripley-card-price"]',
                '[data-test-id="tarjeta-ripley"]',
                '.ripley-card-price',
                '.tarjeta-precio',
                '.tc-ripley-price',
                '.ripley-tarjeta-price',
                '.card-price-ripley',
            ];

            // Analizar precios encontrados
            const publicPriceDetails = [];
            publicSelectors.forEach(selector => {
                const details = getElementDetails(selector);
                if (details.length > 0) {
                    publicPriceDetails.push({
                        selector,
                        elements: details
                    });
                }
            });

            const cardPriceDetails = [];
            cardSelectors.forEach(selector => {
                const details = getElementDetails(selector);
                if (details.length > 0) {
                    cardPriceDetails.push({
                        selector,
                        elements: details
                    });
                }
            });

            return {
                url: window.location.href,
                title: getText('h1') || getText('.product-header__title'),
                jsonLdData,
                publicPriceDetails,
                cardPriceDetails,
                bodyText: document.body.innerText.substring(0, 1000)
            };
        });

        console.log("\n📊 ANÁLISIS DE DETECCIÓN DE PRECIOS");
        console.log("=" * 50);

        console.log("\n🔹 PRECIOS PÚBLICOS DETECTADOS:");
        if (debugData.publicPriceDetails.length === 0) {
            console.log("   ❌ No se detectaron precios públicos");
        } else {
            debugData.publicPriceDetails.forEach(item => {
                console.log(`   📍 Selector: ${item.selector}`);
                item.elements.forEach(el => {
                    const visible = el.display !== 'none' && el.visibility !== 'hidden' && el.opacity !== '0';
                    const price = toNumber(el.text || el.content);
                    console.log(`      - Texto: "${el.text}" | Content: "${el.content}"`);
                    console.log(`      - Precio numérico: ${price} | Visible: ${visible}`);
                    console.log(`      - Clases: "${el.classes}" | Display: ${el.display}`);
                });
            });
        }

        console.log("\n🔹 PRECIOS DE TARJETA DETECTADOS:");
        if (debugData.cardPriceDetails.length === 0) {
            console.log("   ❌ No se detectaron precios de tarjeta");
        } else {
            debugData.cardPriceDetails.forEach(item => {
                console.log(`   📍 Selector: ${item.selector}`);
                item.elements.forEach(el => {
                    const visible = el.display !== 'none' && el.visibility !== 'hidden' && el.opacity !== '0';
                    const price = toNumber(el.text || el.content);
                    console.log(`      - Texto: "${el.text}" | Content: "${el.content}"`);
                    console.log(`      - Precio numérico: ${price} | Visible: ${visible}`);
                    console.log(`      - Clases: "${el.classes}" | Display: ${el.display}`);
                });
            });
        }

        console.log("\n🔹 ANÁLISIS DE JSON-LD:");
        debugData.jsonLdData.forEach((item, index) => {
            console.log(`   📍 Script ${index}:`);
            if (item.error) {
                console.log(`      ❌ Error: ${item.error}`);
            } else {
                console.log(`      - Tipo: ${item.type}`);
                console.log(`      - Nombre: ${item.name}`);
                if (item.offers) {
                    console.log(`      - Ofertas:`, JSON.stringify(item.offers, null, 6).substring(0, 300));
                }
            }
        });

        // Guardar análisis completo
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const debugFile = `storage/app/debug_ripley_${timestamp}.json`;
        fs.writeFileSync(debugFile, JSON.stringify(debugData, null, 2));
        
        console.log(`\n💾 Análisis completo guardado en: ${debugFile}`);
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        process.exitCode = 1;
    } finally {
        try { if (browser) await browser.close(); } catch (e) { }
        const uniqueDirs = Array.from(new Set(createdUserDataDirs));
        uniqueDirs.forEach(cleanupDir);
    }
})();