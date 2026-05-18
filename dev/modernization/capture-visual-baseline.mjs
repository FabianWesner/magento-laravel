#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
  const [key, ...value] = arg.replace(/^--/, '').split('=');
  return [key, value.join('=') || '1'];
}));

if (args.help || !args.url) {
  console.log('Usage: node dev/modernization/capture-visual-baseline.mjs --url=http://example.test --out=.localdev/visual-baseline');
  process.exit(args.help ? 0 : 2);
}

const out = args.out || '.localdev/visual-baseline';
const viewports = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'laptop', width: 1280, height: 900 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'mobile', width: 390, height: 844 },
];

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch {
  console.error('Playwright package is not installed. Install it in a local tooling workspace or run via npx playwright.');
  process.exit(2);
}

await fs.mkdir(out, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60000 });
    await page.screenshot({ path: path.join(out, `${viewport.name}.png`), fullPage: true });
    await page.close();
    console.log(`captured ${viewport.name}`);
  }
} finally {
  await browser.close();
}
