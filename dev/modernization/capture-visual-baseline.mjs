#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
  const [key, ...value] = arg.replace(/^--/, '').split('=');
  return [key, value.join('=') || '1'];
}));

if (args.help || !args.url) {
  console.log('Usage: node dev/modernization/capture-visual-baseline.mjs --url=http://example.test --out=.localdev/visual-baseline');
  console.log('');
  console.log('Optional manifest output:');
  console.log('  --manifest=specs/modernization/ui-screen-inventory.md');
  console.log('  --runtime=magento|laravel');
  console.log('  --screen-id=SF-HOME');
  console.log('  --feature-ids=SF-001,SF-002');
  console.log('  --role=guest');
  console.log('  --fixture-id=canonical-demo');
  console.log('  --state=default');
  console.log('  --parity-decision=preserve|bridge|replace|retire');
  process.exit(args.help ? 0 : 2);
}

const out = args.out || '.localdev/visual-baseline';
const manifest = args.manifest || '';
const manifestContext = manifest ? validateManifestContext(args) : null;
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
const manifestRows = [];
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60000 });
    const screenshotPath = path.join(out, `${viewport.name}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });
    await page.close();
    console.log(`captured ${viewport.name}`);
    if (manifestContext) {
      manifestRows.push({
        ...manifestContext,
        viewport: viewport.name,
        capturedAt: new Date().toISOString(),
        artifactPath: toPosixPath(screenshotPath),
      });
    }
  }
} finally {
  await browser.close();
}

if (manifest && manifestRows.length > 0) {
  await appendManifestRows(manifest, manifestRows);
  console.log(`appended ${manifestRows.length} manifest rows to ${manifest}`);
}

function validateManifestContext(options) {
  const required = ['runtime', 'screen-id', 'feature-ids', 'role', 'fixture-id', 'state', 'parity-decision'];
  for (const key of required) {
    if (!options[key]) {
      fail(`--${key} is required when --manifest is provided.`);
    }
  }

  const runtime = options.runtime.toLowerCase();
  if (!['magento', 'laravel'].includes(runtime)) {
    fail('--runtime must be magento or laravel.');
  }

  const parityDecision = options['parity-decision'].toLowerCase();
  if (!['preserve', 'bridge', 'replace', 'retire'].includes(parityDecision)) {
    fail('--parity-decision must be preserve, bridge, replace, or retire.');
  }

  return {
    screenId: options['screen-id'],
    featureIds: options['feature-ids'],
    runtime,
    url: options.url,
    role: options.role,
    fixtureId: options['fixture-id'],
    state: options.state,
    parityDecision,
  };
}

async function appendManifestRows(manifestPath, rows) {
  await fs.mkdir(path.dirname(manifestPath), { recursive: true });
  const header = [
    '| Screen ID | Feature IDs | Runtime | URL | Role | Fixture ID | Viewport | State | Captured At | Artifact Path | Parity Decision |',
    '| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |',
  ].join('\n');
  const body = rows.map((row) => [
    row.screenId,
    row.featureIds,
    row.runtime,
    row.url,
    row.role,
    row.fixtureId,
    row.viewport,
    row.state,
    row.capturedAt,
    row.artifactPath,
    row.parityDecision,
  ].map(markdownCell).join(' | ')).map((row) => `| ${row} |`).join('\n');

  let existing = '';
  try {
    existing = await fs.readFile(manifestPath, 'utf8');
  } catch (error) {
    if (error.code !== 'ENOENT') {
      throw error;
    }
  }

  const needsHeader = existing.trim() === '';
  const prefix = needsHeader ? `${header}\n` : (existing.endsWith('\n') ? '' : '\n');
  await fs.appendFile(manifestPath, `${prefix}${body}\n`);
}

function markdownCell(value) {
  return String(value)
    .replace(/\r?\n/g, ' ')
    .replace(/\|/g, '\\|')
    .trim();
}

function toPosixPath(filePath) {
  return filePath.split(path.sep).join(path.posix.sep);
}

function fail(message) {
  console.error(message);
  process.exit(1);
}
