#!/usr/bin/env node
import { createRequire } from 'node:module';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDir, '../..');
const docusaurusRoot = path.resolve(repoRoot, 'docusaurus');
const require = createRequire(import.meta.url);

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
  console.log('  --evidence=specs/modernization/visual-smoke-evidence.md');
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
  try {
    ({ chromium } = require(path.resolve(docusaurusRoot, 'node_modules/playwright')));
  } catch {
    console.error('Playwright package is not installed. Install it in a local tooling workspace, install Docusaurus dependencies, or run via npx playwright.');
    process.exit(2);
  }
}

await fs.mkdir(out, { recursive: true });
const manifestRows = [];
const evidenceRows = [];
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    const response = await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60000 });
    const screenshotPath = path.join(out, `${viewport.name}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });
    const title = await page.title();
    const file = await fs.stat(screenshotPath);
    await page.close();
    console.log(`captured ${viewport.name}`);
    evidenceRows.push({
      viewport: viewport.name,
      width: viewport.width,
      height: viewport.height,
      status: response?.status() ?? null,
      title,
      artifactPath: toPosixPath(screenshotPath),
      bytes: file.size,
    });
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

if (args.evidence) {
  await writeEvidence(args.evidence, evidenceRows, manifestContext);
  console.log(`wrote evidence to ${args.evidence}`);
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

async function writeEvidence(evidencePath, rows, context) {
  const resolvedPath = resolveRepoPath(evidencePath);
  await fs.mkdir(path.dirname(resolvedPath), { recursive: true });

  const metadata = context ?? {
    screenId: args['screen-id'] || 'ad-hoc',
    featureIds: args['feature-ids'] || 'not-specified',
    runtime: args.runtime || 'not-specified',
    role: args.role || 'not-specified',
    fixtureId: args['fixture-id'] || 'not-specified',
    state: args.state || 'not-specified',
    parityDecision: args['parity-decision'] || 'not-specified',
  };

  const lines = [
    '# Visual Smoke Evidence',
    '',
    `Generated At: ${new Date().toISOString()}`,
    `Command: node dev/modernization/capture-visual-baseline.mjs ${process.argv.slice(2).join(' ')}`,
    `URL: ${args.url}`,
    `Runtime: ${metadata.runtime}`,
    `Screen ID: ${metadata.screenId}`,
    `Feature IDs: ${metadata.featureIds}`,
    `Role: ${metadata.role}`,
    `Fixture ID: ${metadata.fixtureId}`,
    `State: ${metadata.state}`,
    `Parity Decision: ${metadata.parityDecision}`,
    'Status: Local visual smoke only',
    '',
    '| Viewport | Size | HTTP Status | Page Title | Artifact Path | Bytes |',
    '| --- | --- | ---: | --- | --- | ---: |',
    ...rows.map((row) => `| ${row.viewport} | ${row.width}x${row.height} | ${row.status ?? 'n/a'} | ${markdownCell(row.title)} | \`${row.artifactPath}\` | ${row.bytes} |`),
    '',
    '## Notes',
    '',
    'This evidence records a local screenshot smoke capture only. It is not a complete Magento/Laravel screenshot manifest, visual regression approval, accessibility evidence, manual acceptance, or release evidence.',
    '',
  ];

  await fs.writeFile(resolvedPath, lines.join('\n'), 'utf8');
}

function resolveRepoPath(filePath) {
  const resolvedPath = path.resolve(repoRoot, filePath);
  if (resolvedPath !== repoRoot && !resolvedPath.startsWith(`${repoRoot}${path.sep}`)) {
    fail(`Evidence path must stay inside the repository: ${filePath}`);
  }

  return resolvedPath;
}

function fail(message) {
  console.error(message);
  process.exit(1);
}
