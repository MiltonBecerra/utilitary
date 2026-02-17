const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const inputPath = process.argv[2];

if (!inputPath) {
    console.error('Missing input JSON path');
    process.exit(1);
}

const readPayload = (filePath) => {
    const raw = fs.readFileSync(filePath, 'utf-8');
    return JSON.parse(raw);
};

const ensureDir = (dirPath) => {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
};

const safeName = (value) => sanitizeQuery(value || 'item').replace(/\s+/g, '_').slice(0, 80) || 'item';

const normalize = (text) => String(text || '').trim();

const isPlazaVeaUrl = (value) => {
    try {
        const parsed = new URL(value);
        return parsed.hostname.includes('plazavea.com.pe');
    } catch (e) {
        return false;
    }
};

const sanitizeQuery = (value) => {
    const text = normalize(value).toLowerCase();
    if (!text) return '';
    return text
        .replace(/[’']/g, '')
        .replace(/&/g, ' ')
        .replace(/[\p{P}\p{S}]+/gu, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const ensureUrl = (value) => {
    const raw = normalize(value);
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    if (raw.includes('plazavea.com.pe')) return `https://${raw.replace(/^\/+/, '')}`;
    return raw;
};

const buildQueryVariants = (value) => {
    const variants = new Set();
    const raw = normalize(value);
    if (raw) variants.add(raw);
    const cleaned = sanitizeQuery(raw);
    if (cleaned) variants.add(cleaned);
    return Array.from(variants);
};

const fetchJson = async (url) => {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return null;
    return res.json();
};

const gotoWithRetry = async (page, targetUrl, options = {}, retries = 2) => {
    let lastError = null;
    for (let attempt = 1; attempt <= retries; attempt += 1) {
        try {
            await page.goto(targetUrl, options);
            return true;
        } catch (error) {
            lastError = error;
            if (attempt < retries) {
                await page.waitForTimeout(1000 * attempt);
                continue;
            }
        }
    }
    if (lastError) throw lastError;
    return false;
};

const buildSearchQueries = (item) => {
    const queries = new Set();
    const title = normalize(item.title);
    buildQueryVariants(title).forEach((q) => queries.add(q));
    const url = ensureUrl(item.url);
    if (url && isPlazaVeaUrl(url)) {
        try {
            const parsed = new URL(url);
            const parts = parsed.pathname.split('/').filter(Boolean);
            if (parts.length) {
                const last = decodeURIComponent(parts[parts.length - 1]);
                if (last && last !== 'p') {
                    buildQueryVariants(last.replace(/-/g, ' ')).forEach((q) => queries.add(q));
                }
                if (parts.includes('p') && parts.length >= 2) {
                    const slug = parts[parts.length - 2];
                    if (slug) buildQueryVariants(slug.replace(/-/g, ' ')).forEach((q) => queries.add(q));
                }
            }
        } catch (e) {
            // ignore invalid URL
        }
    }
    return Array.from(queries).filter(Boolean);
};

const scoreProduct = (query, productName) => {
    const q = sanitizeQuery(query);
    const p = sanitizeQuery(productName);
    if (!q || !p) return 0;
    const qTokens = q.split(' ').filter(Boolean);
    if (!qTokens.length) return 0;
    let matches = 0;
    for (const token of qTokens) {
        if (p.includes(token)) matches += 1;
    }
    return (matches / qTokens.length) * 100;
};

const pickBestProduct = (query, products) => {
    if (!Array.isArray(products) || !products.length) return null;
    let best = null;
    let bestScore = 0;
    for (const product of products) {
        const score = scoreProduct(query, product?.productName || product?.productTitle || '');
        if (score > bestScore) {
            best = product;
            bestScore = score;
        }
    }
    if (best && bestScore >= 60) return best;
    return products[0];
};

const fetchProductByQuery = async (query) => {
    const endpoints = [
        `https://www.plazavea.com.pe/api/catalog_system/pub/products/search/${encodeURIComponent(query)}`,
        `https://www.plazavea.com.pe/api/catalog_system/pub/products/search/?ft=${encodeURIComponent(query)}`,
    ];
    for (const url of endpoints) {
        try {
            const data = await fetchJson(url);
            if (Array.isArray(data) && data.length) {
                return pickBestProduct(query, data);
            }
        } catch (e) {
            // ignore and try next
        }
    }
    return null;
};

const getAddToCartLink = (product, qty) => {
    const items = Array.isArray(product?.items) ? product.items : [];
    if (!items.length) return null;
    const item = items[0];
    const sellers = Array.isArray(item.sellers) ? item.sellers : [];
    const seller = sellers.find((s) => s.sellerDefault) || sellers[0];
    const link = seller?.addToCartLink || null;
    if (!link) return null;
    try {
        const url = new URL(link);
        url.searchParams.set('qty', String(qty || 1));
        return url.toString();
    } catch (e) {
        return link;
    }
};

const waitForAny = async (locators, timeout = 6000) => {
    const started = Date.now();
    for (;;) {
        for (const locator of locators) {
            try {
                if (await locator.first().isVisible()) {
                    return locator.first();
                }
            } catch (e) {
                // ignore
            }
        }
        if (Date.now() - started > timeout) return null;
        await new Promise((r) => setTimeout(r, 200));
    }
};

const clickIfVisible = async (locator, timeout = 2000) => {
    try {
        const target = await waitForAny([locator], timeout);
        if (target) {
            await target.click({ timeout: 2000 });
            return true;
        }
    } catch (e) {
        return false;
    }
    return false;
};

const dismissCookiesAndPopups = async (page) => {
    const cookieButtons = [
        page.getByRole('button', { name: /aceptar/i }),
        page.getByRole('button', { name: /confirmar/i }),
        page.getByRole('button', { name: /rechazar/i }),
    ];

    for (const btn of cookieButtons) {
        await clickIfVisible(btn, 1500);
    }

    const modalButtons = [
        page.getByRole('button', { name: /cerrar/i }),
        page.getByRole('button', { name: /mantener/i }),
        page.getByRole('button', { name: /continuar/i }),
    ];
    for (const btn of modalButtons) {
        await clickIfVisible(btn, 1200);
    }
};

const findAddToCartButton = async (page) => {
    const selectors = [
        'button[data-testid="buy-button"]',
        'button:has-text("Agregar al carrito")',
        'button:has-text("Anadir al carrito")',
        'button:has-text("Agregar al Carrito")',
        'button:has-text("Agregar")',
    ];

    const locators = selectors.map((sel) => page.locator(sel));
    return await waitForAny(locators, 8000);
};

const isOutOfStock = async (page) => {
    const selectors = [
        'text=/sin stock/i',
        'text=/agotado/i',
        'text=/no disponible/i',
    ];
    for (const sel of selectors) {
        try {
            if (await page.locator(sel).first().isVisible()) {
                return true;
            }
        } catch (e) {
            // ignore
        }
    }
    return false;
};

const run = async () => {
    const payload = readPayload(inputPath);
    const items = Array.isArray(payload.items) ? payload.items : [];
    const headless = payload.headless !== false;
    const keepOpen = payload.keep_open === true && !headless;
    const debugScreenshots = payload.debug_screenshots === true || !headless;
    const runId = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
    const debugDir = path.resolve(process.cwd(), 'storage', 'app', 'smc_cart_debug', runId);
    if (debugScreenshots) ensureDir(debugDir);

    const browser = await chromium.launch({
        headless,
        slowMo: 50,
    });

    const page = await browser.newPage({
        viewport: { width: 1440, height: 900 },
    });
    page.setDefaultTimeout(20000);

    const results = {
        ok: true,
        store: payload.store || 'plaza_vea',
        run_headless: headless,
        keep_open: keepOpen,
        debug_dir: debugScreenshots ? debugDir : null,
        added: [],
        failed: [],
    };

    for (const item of items) {
        const url = ensureUrl(item.url);
        const title = normalize(item.title);
        const triedQueries = [];
        if (!url) {
            results.failed.push({
                url: '',
                title,
                reason: 'Producto sin URL',
                queries: triedQueries,
            });
            continue;
        }

        try {
            const queries = buildSearchQueries(item);
            let product = null;
            let matchedQuery = null;
            for (const q of queries) {
                triedQueries.push(q);
                product = await fetchProductByQuery(q);
                if (product) {
                    matchedQuery = q;
                    break;
                }
            }

            if (product) {
                const addLink = getAddToCartLink(product, item.quantity || 1);
                if (addLink) {
                    await gotoWithRetry(page, addLink, { waitUntil: 'domcontentloaded', timeout: 45000 }, 3);
                    await page.waitForTimeout(1200);
                    if (debugScreenshots) {
                        const shot = path.join(debugDir, `${safeName(title)}_added_api.png`);
                        await page.screenshot({ path: shot, fullPage: true });
                    }
                    results.added.push({
                        url,
                        title,
                        method: 'api',
                        matched_query: matchedQuery,
                        product_id: product.productId || null,
                        product_link: product.link || null,
                    });
                    continue;
                }
            }

            if (!isPlazaVeaUrl(url)) {
                results.failed.push({
                    url,
                    title,
                    reason: 'URL no es de Plaza Vea',
                    queries: triedQueries,
                });
                continue;
            }

            await gotoWithRetry(page, url, { waitUntil: 'domcontentloaded', timeout: 45000 }, 3);
            await dismissCookiesAndPopups(page);

            if (await isOutOfStock(page)) {
                results.failed.push({ url, title, reason: 'Sin stock', queries: triedQueries });
                continue;
            }

            const addButton = await findAddToCartButton(page);
            if (!addButton) {
                results.failed.push({ url, title, reason: 'Botón de agregar no encontrado', queries: triedQueries });
                continue;
            }

            const disabled = await addButton.isDisabled().catch(() => false);
            if (disabled) {
                results.failed.push({ url, title, reason: 'Botón deshabilitado', queries: triedQueries });
                continue;
            }

            await addButton.click({ timeout: 8000 });
            await dismissCookiesAndPopups(page);
            await page.waitForTimeout(1500);

            if (debugScreenshots) {
                const shot = path.join(debugDir, `${safeName(title)}_added_ui.png`);
                await page.screenshot({ path: shot, fullPage: true });
            }

            results.added.push({ url, title, method: 'ui' });
        } catch (error) {
            if (debugScreenshots) {
                try {
                    const shot = path.join(debugDir, `${safeName(title)}_failed.png`);
                    await page.screenshot({ path: shot, fullPage: true });
                } catch (e) {
                    // ignore screenshot error
                }
            }
            results.failed.push({
                url,
                title,
                reason: error && error.message ? error.message : 'Error desconocido',
                queries: triedQueries,
            });
        }
    }

    if (keepOpen) {
        // Keep browser open for manual verification. Continue only after user closes it.
        await new Promise((resolve) => {
            browser.on('disconnected', resolve);
        });
    } else {
        await browser.close();
    }
    return results;
};

run()
    .then((result) => {
        console.log(JSON.stringify(result, null, 2));
    })
    .catch((error) => {
        console.error(error && error.message ? error.message : String(error));
        process.exit(1);
    });
