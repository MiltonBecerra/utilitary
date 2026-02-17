const { chromium } = require('playwright');

const args = process.argv.slice(2);
const keepOpen = args.includes('--keep-open');
const filteredArgs = args.filter((arg) => arg !== '--keep-open');
const [phoneArg, ...messageParts] = filteredArgs;

if (!phoneArg || messageParts.length === 0) {
  console.log('Usage: node scripts/whatsapp_send.js <phoneE164> <message> [--keep-open]');
  console.log('Example: node scripts/whatsapp_send.js 51999999999 "Alerta: precio bajo" --keep-open');
  process.exit(1);
}

const phone = phoneArg.replace(/[^0-9]/g, '');
const message = messageParts.join(' ');

if (!phone) {
  console.error('Invalid phone. Use digits only (E.164 without +).');
  process.exit(1);
}

const userDataDir = 'C:/xampp/htdocs/utilitary/.whatsapp-profile';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForWhatsAppReady(page) {
  const qrSelector = 'canvas[aria-label="Scan me!"]';
  const chatListSelector = '#pane-side';

  for (let i = 0; i < 60; i += 1) {
    const hasChatList = await page.locator(chatListSelector).count();
    if (hasChatList) return;

    const hasQr = await page.locator(qrSelector).count();
    if (hasQr) {
      console.log('Waiting for QR scan...');
    }

    await sleep(1000);
  }

  throw new Error('WhatsApp Web did not become ready in time.');
}

async function main() {
  const context = await chromium.launchPersistentContext(userDataDir, {
    headless: false,
    args: ['--start-maximized'],
    viewport: null,
  });

  const page = await context.newPage();
  await page.goto('https://web.whatsapp.com/', { waitUntil: 'domcontentloaded' });
  await waitForWhatsAppReady(page);

  const url = `https://web.whatsapp.com/send?phone=${encodeURIComponent(phone)}&text=${encodeURIComponent(message)}`;
  await page.goto(url, { waitUntil: 'domcontentloaded' });

  const messageBox = page.locator('div[contenteditable="true"][data-tab]');
  await messageBox.first().waitFor({ timeout: 30000 });
  await sleep(1000);
  await page.keyboard.press('Enter');

  if (keepOpen) {
    console.log('Message sent. Browser will stay open.');
    await new Promise(() => {});
  }

  console.log('Message sent. Closing browser.');
  await context.close();
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
