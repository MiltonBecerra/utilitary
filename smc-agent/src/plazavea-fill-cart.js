const { chromium } = require('playwright');

const normalize = (text) => String(text || '').trim();

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
    if (best && bestScore >= 60) {
        return best;
    }
    return products[0];
};

const fetchProductByQuery = async (query) => {
    const endpoint = `https://www.plazavea.com.pe/api/catalog_system/pub/products/search/${encodeURIComponent(query)}`;
    const response = await fetch(endpoint, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
        return null;
    }
    const data = await response.json();
    return pickBestProduct(query, data);
};

const getAddToCartLink = (product, qty) => {
    const items = Array.isArray(product?.items) ? product.items : [];
    if (!items.length) return null;
    const item = items[0];
    const sellers = Array.isArray(item.sellers) ? item.sellers : [];
    const seller = sellers.find((s) => s.sellerDefault) || sellers[0];
    const link = seller?.addToCartLink || null;
    if (!link) return null;
    const addUrl = new URL(link);
    addUrl.searchParams.set('qty', String(qty || 1));
    return addUrl.toString();
};

const runPlazaVeaFill = async ({ items, headless, keepOpen, onProgress }) => {
    let browser;
    const result = {
        added: [],
        failed: [],
    };

    try {
        browser = await chromium.launch({
            headless,
            channel: 'chrome',
            slowMo: 60,
        });
    } catch (error) {
        browser = await chromium.launch({
            headless,
            slowMo: 60,
        });
    }

    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    page.setDefaultTimeout(25000);

    for (let index = 0; index < items.length; index += 1) {
        const item = items[index];
        const title = normalize(item.title);
        const query = sanitizeQuery(title || item.url || '');

        try {
            const product = await fetchProductByQuery(query);
            if (!product) {
                result.failed.push({
                    title,
                    url: item.url || null,
                    reason: 'No se encontro producto en API de Plaza Vea',
                });
                if (onProgress) await onProgress(index + 1, items.length, title);
                continue;
            }

            const link = getAddToCartLink(product, item.quantity || 1);
            if (!link) {
                result.failed.push({
                    title,
                    url: item.url || null,
                    reason: 'Producto sin addToCartLink',
                });
                if (onProgress) await onProgress(index + 1, items.length, title);
                continue;
            }

            await page.goto(link, { waitUntil: 'domcontentloaded', timeout: 45000 });
            await page.waitForTimeout(1400);

            result.added.push({
                title,
                url: item.url || null,
                product_id: product.productId || null,
                product_link: product.link || null,
            });
        } catch (error) {
            result.failed.push({
                title,
                url: item.url || null,
                reason: error?.message || 'Error inesperado',
            });
        }

        if (onProgress) {
            await onProgress(index + 1, items.length, title);
        }
    }

    if (keepOpen && !headless) {
        await new Promise((resolve) => {
            browser.on('disconnected', resolve);
        });
    } else {
        await browser.close();
    }

    return result;
};

module.exports = {
    runPlazaVeaFill,
};
