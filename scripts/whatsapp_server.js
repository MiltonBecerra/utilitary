const express = require('express');
const { chromium } = require('playwright');

const PORT = process.env.WHATSAPP_SERVER_PORT || 3010;
const API_KEY = process.env.WHATSAPP_API_KEY || 'utilitary-wa-key';
const USER_DATA_DIR = 'C:/xampp/htdocs/utilitary/.whatsapp-profile';

let browserContext = null;
let page = null;
let sending = Promise.resolve();

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForWhatsAppReady(currentPage) {
  const qrSelector = 'canvas[aria-label="Scan me!"]';
  const chatListSelector = '#pane-side';

  for (let i = 0; i < 120; i += 1) {
    if (await currentPage.locator(chatListSelector).count()) {
      return;
    }
    if (await currentPage.locator(qrSelector).count()) {
      console.log('Waiting for QR scan...');
    }
    await sleep(1000);
  }

  throw new Error('WhatsApp Web did not become ready in time.');
}

async function ensureSession() {
  if (browserContext && page) {
    return;
  }

  browserContext = await chromium.launchPersistentContext(USER_DATA_DIR, {
    headless: false,
    args: ['--start-maximized'],
    viewport: null,
  });

  page = await browserContext.newPage();
  await page.goto('https://web.whatsapp.com/', { waitUntil: 'domcontentloaded' });
  await waitForWhatsAppReady(page);
}

async function sendMessage(phone, message) {
  await ensureSession();
  const digits = String(phone).replace(/[^0-9]/g, '');
  if (!digits) {
    throw new Error('Invalid phone number');
  }

  const searchBox = page.locator('div[contenteditable="true"][data-tab="3"], [data-testid="chat-list-search"] div[contenteditable="true"]');
  if (await searchBox.count()) {
    await searchBox.first().click();
    await page.keyboard.press('Control+A');
    await page.keyboard.press('Backspace');
    await searchBox.first().type(digits, { delay: 40 });
    await sleep(800);

    const chatResult = page.locator('#pane-side span[title]').first();
    if (await chatResult.count()) {
      await chatResult.click();
      await sleep(400);
    } else {
      const url = `https://web.whatsapp.com/send?phone=${encodeURIComponent(digits)}&text=${encodeURIComponent(message)}`;
      await page.goto(url, { waitUntil: 'domcontentloaded' });
    }
  }

  const messageBox = page.locator('footer div[contenteditable="true"][data-tab], [data-testid="conversation-compose-box-input"]');
  await messageBox.first().waitFor({ timeout: 30000 });
  await messageBox.first().click();
  await messageBox.first().type(message, { delay: 20 });
  await page.keyboard.press('Enter');
  await sleep(800);

  if (await searchBox.count()) {
    await searchBox.first().click();
    await page.keyboard.press('Control+A');
    await page.keyboard.press('Backspace');
  }
}

const app = express();
app.use(express.json());

app.get('/health', async (_req, res) => {
  res.json({ status: 'ok' });
});

app.post('/send', async (req, res) => {
  const providedKey = req.headers['x-api-key'];
  if (providedKey !== API_KEY) {
    return res.status(401).json({ success: false, error: 'Invalid API key' });
  }

  const { phone, message } = req.body || {};
  if (!phone || !message) {
    return res.status(400).json({ success: false, error: 'phone and message required' });
  }

  sending = sending.then(async () => {
    await sendMessage(phone, message);
  });

  try {
    await sending;
    return res.json({ success: true });
  } catch (error) {
    return res.status(500).json({ success: false, error: error.message });
  }
});

app.listen(PORT, () => {
  console.log(`WhatsApp server running on http://localhost:${PORT}`);
});
