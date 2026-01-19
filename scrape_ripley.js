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
                if (normalized.includes('.') && normalized.includes(',')) {
                    normalized = normalized.replace(/\./g, '').replace(',', '.');
                } else if (normalized.includes(',') && !normalized.includes('.')) {
                    normalized = normalized.replace(',', '.');
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
            const scripts = document.querySelectorAll('script[type="application/ld+json"]');
            for (let script of scripts) {
                try {
                    const json = JSON.parse(script.innerText);
                    if (json['@type'] === 'Product' || json['@type'] === 'ProductGroup') {
                        data = json;
                        break;
                    }
                    if (Array.isArray(json) && json[0]['@type'] === 'Product') {
                        data = json[0];
                        break;
                    }
                } catch (e) { }
            }

            // Fallback Selectors
            const publicPriceSelectors = [
                '.product-price__final-price',
                '.product-prices__price',
                '.fbra_text--product-price'
            ];

            const cardPriceSelectors = [
                '.product-price__card-price',
                '.product-price__card',
                '.product-prices__card-price',
                '[data-testid="card-price"]'
            ];

            const bodyText = document.body ? document.body.innerText : '';

            let publicPrice = null;
            if (data.offers && data.offers.price) {
                publicPrice = toNumber(data.offers.price);
            }
            if (publicPrice === null) {
                publicPrice = firstPriceFromSelectors(publicPriceSelectors);
            }

            let cardPrice = firstPriceFromSelectors(cardPriceSelectors);
            if (cardPrice === null && bodyText) {
                const regexes = [
                    /Tarjeta\s+(?:Ripley|Banco\s+Ripley)[\s\S]{0,120}?(?:S\/\s*)?([0-9][0-9.,]*)/i,
                    /(?:T\.?C\.?|TC)\s+Ripley[\s\S]{0,120}?(?:S\/\s*)?([0-9][0-9.,]*)/i,
                ];
                for (const re of regexes) {
                    const m = bodyText.match(re);
                    if (m && m[1]) {
                        const n = toNumber(m[1]);
                        if (n !== null) {
                            cardPrice = n;
                            break;
                        }
                    }
                }
            }

            const title = data.name || getText('h1') || getText('.product-header__title');
            const image = data.image || getAttr('meta[property="og:image"]', 'content');

            return {
                title,
                price: publicPrice ?? cardPrice,
                public_price: publicPrice,
                card_price: cardPrice,
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
