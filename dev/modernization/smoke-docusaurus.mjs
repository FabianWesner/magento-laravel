#!/usr/bin/env node
import { createServer } from 'node:http';
import { createRequire } from 'node:module';
import { dirname, extname, join, normalize, resolve, sep } from 'node:path';
import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(scriptDir, '../..');
const docusaurusRoot = resolve(repoRoot, 'docusaurus');
const buildRoot = resolve(docusaurusRoot, 'build');
const host = '127.0.0.1';
const port = Number(process.env.DOCUSAURUS_SMOKE_PORT || 3012);
const baseUrl = `http://${host}:${port}`;
const paths = ['/', '/user/', '/developer/'];
const require = createRequire(import.meta.url);
const evidencePath = parseEvidencePath(process.argv.slice(2));
const startedAt = new Date();

let chromium;
try {
  ({ chromium } = require(resolve(docusaurusRoot, 'node_modules/playwright')));
} catch {
  console.error('Playwright package is not installed. Run npm install --prefix docusaurus first.');
  process.exit(2);
}

const contentTypes = {
  '.css': 'text/css; charset=utf-8',
  '.html': 'text/html; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8',
};

function parseEvidencePath(args) {
  const evidenceIndex = args.indexOf('--evidence');
  const evidenceValue = evidenceIndex === -1 ? null : args[evidenceIndex + 1];
  const inlineEvidence = args.find((arg) => arg.startsWith('--evidence='));
  const rawEvidencePath = evidenceValue ?? inlineEvidence?.slice('--evidence='.length) ?? null;

  if (rawEvidencePath === null || rawEvidencePath === '') {
    return null;
  }

  const resolved = resolve(repoRoot, rawEvidencePath);
  if (resolved !== repoRoot && !resolved.startsWith(`${repoRoot}${sep}`)) {
    throw new Error(`Evidence path must stay inside the repository: ${rawEvidencePath}`);
  }

  return resolved;
}

function safePath(pathname) {
  const decoded = decodeURIComponent(pathname.split('?')[0] ?? '/');
  const normalized = normalize(decoded).replace(/^(\.\.(\/|\\|$))+/, '');
  return resolve(buildRoot, `.${sep}${normalized}`);
}

async function resolveFile(pathname) {
  let candidate = safePath(pathname);

  if (!candidate.startsWith(buildRoot)) {
    return null;
  }

  try {
    const fileStat = await stat(candidate);
    if (fileStat.isDirectory()) {
      candidate = join(candidate, 'index.html');
    }
  } catch {
    candidate = join(candidate, 'index.html');
  }

  if (!candidate.startsWith(buildRoot)) {
    return null;
  }

  try {
    const fileStat = await stat(candidate);
    return fileStat.isFile() ? candidate : null;
  } catch {
    return null;
  }
}

const server = createServer(async (request, response) => {
  const file = await resolveFile(request.url ?? '/');
  if (file === null) {
    if ((request.url ?? '').split('?')[0]?.endsWith('.ico')) {
      response.writeHead(204);
      response.end();
      return;
    }

    response.writeHead(404, { 'content-type': 'text/plain; charset=utf-8' });
    response.end('Not found');
    return;
  }

  const body = await readFile(file);
  response.writeHead(200, {
    'content-type': contentTypes[extname(file)] ?? 'application/octet-stream',
  });
  response.end(body);
});

async function writeEvidence(results, consoleMessages) {
  if (evidencePath === null) {
    return;
  }

  const relativeEvidencePath = evidencePath.slice(repoRoot.length + 1);
  const lines = [
    '# Docusaurus Browser Smoke Evidence',
    '',
    `Generated At: ${new Date().toISOString()}`,
    `Started At: ${startedAt.toISOString()}`,
    `Command: node dev/modernization/smoke-docusaurus.mjs --evidence ${relativeEvidencePath}`,
    `Base URL: ${baseUrl}`,
    'Browser: Chrome via Playwright',
    'Status: Pass',
    '',
    '| Path | HTTP Status | Page Title |',
    '| --- | --- | --- |',
    ...results.map((result) => `| \`${result.pathname}\` | ${result.status} | ${escapeMarkdownTable(result.title)} |`),
    '',
    '## Browser Console',
    '',
    consoleMessages.length === 0
      ? 'No browser console warnings or errors were reported.'
      : consoleMessages.map((message) => `- ${message}`).join('\n'),
    '',
    '## Notes',
    '',
    'This evidence records the Docusaurus static build browser smoke only. It does not close release checklist, CI, fixture, visual, security, accessibility, or cutover defects.',
    '',
  ];

  await mkdir(dirname(evidencePath), { recursive: true });
  await writeFile(evidencePath, lines.join('\n'), 'utf8');
  console.log(`WROTE: ${relativeEvidencePath}`);
}

function escapeMarkdownTable(value) {
  return value.replaceAll('\\', '\\\\').replaceAll('|', '\\|');
}

try {
  await new Promise((resolveListen, rejectListen) => {
    server.once('error', rejectListen);
    server.listen(port, host, resolveListen);
  });
} catch (error) {
  if (['EACCES', 'EPERM'].includes(error.code)) {
    console.error(`Docusaurus browser smoke unavailable: cannot listen on ${baseUrl} (${error.code}).`);
    process.exit(2);
  }

  throw error;
}

try {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  try {
    const page = await browser.newPage();
    const consoleMessages = [];
    const results = [];
    page.on('console', (message) => {
      if (['warning', 'error'].includes(message.type())) {
        consoleMessages.push(`${message.type()}: ${message.text()}`);
      }
    });

    for (const pathname of paths) {
      const response = await page.goto(`${baseUrl}${pathname}`, {
        waitUntil: 'networkidle',
        timeout: 30000,
      });

      if (!response || !response.ok()) {
        throw new Error(`Docusaurus smoke failed for ${pathname}: HTTP ${response?.status() ?? 'none'}`);
      }

      const title = await page.title();
      results.push({
        pathname,
        status: response.status(),
        title,
      });
      console.log(`PASS: ${pathname} ${title}`);
    }

    if (consoleMessages.length > 0) {
      throw new Error(`Docusaurus browser console warnings/errors:\n${consoleMessages.join('\n')}`);
    }

    await writeEvidence(results, consoleMessages);
  } finally {
    await browser.close();
  }
} finally {
  await new Promise((resolveClose) => server.close(resolveClose));
}
