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

const extractResults = (data) => {
    if (!data || typeof data !== 'object') {
        return null;
    }
    if (data.props && data.props.pageProps) {
        if (Array.isArray(data.props.pageProps.results)) {
            return data.props.pageProps.results;
        }
        if (data.props.pageProps.search && Array.isArray(data.props.pageProps.search.results)) {
            return data.props.pageProps.search.results;
        }
    }
    return null;
};

(async () => {
    let browser = null;
    let userDataDir = null;
    const profileBase = resolveProfileBase();
    const NAV_TIMEOUT = 20000;
    try {
        const launchResult = await launchWithFreshProfile(profileBase);
        browser = launchResult.browserInstance;
        userDataDir = launchResult.userDataDir;

        const page = await browser.newPage();
        page.setDefaultNavigationTimeout(NAV_TIMEOUT);
        page.setDefaultTimeout(NAV_TIMEOUT);
        await page.setViewport({ width: 1920, height: 1080 });

        await page.setRequestInterception(true);
        page.on('request', (request) => {
            const type = request.resourceType();
            if (['image', 'media', 'font', 'stylesheet'].includes(type)) {
                request.abort();
                return;
            }
            request.continue();
        });

        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT });
        if (response && response.status() === 403) {
            console.error(`HTTP 403 for ${url}`);
            await browser.close();
            browser = null;
            cleanupDir(userDataDir);
            process.exit(1);
        }

        const bodyText = await page.evaluate(() => document.body.innerText);
        if (bodyText.includes('Access Denied') || bodyText.includes('403 Forbidden')) {
            console.error('Blocked by Cloudflare (Access Denied)');
            await browser.close();
            browser = null;
            cleanupDir(userDataDir);
            process.exit(1);
        }

        try {
            await page.waitForSelector('script#__NEXT_DATA__', { timeout: 8000 });
        } catch (e) {
            console.error('No __NEXT_DATA__ within timeout');
        }

        const payload = await page.evaluate(() => {
            const node = document.querySelector('script#__NEXT_DATA__');
            if (!node || !node.textContent) {
                return null;
            }
            try {
                return JSON.parse(node.textContent);
            } catch (e) {
                return null;
            }
        });

        const results = extractResults(payload);

        console.log(JSON.stringify({
            url,
            results: Array.isArray(results) ? results : null
        }));
    } catch (error) {
        console.error('Error:', error.message);
        process.exitCode = 1;
    } finally {
        try { if (browser) await browser.close(); } catch (e) { }
        const uniqueDirs = Array.from(new Set(createdUserDataDirs));
        uniqueDirs.forEach(cleanupDir);
    }
})();
