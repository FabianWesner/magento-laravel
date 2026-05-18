#!/usr/bin/env node
const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
  const [key, ...value] = arg.replace(/^--/, '').split('=');
  return [key, value.join('=') || '1'];
}));

if (args.help || !args.url) {
  console.log('Usage: node dev/modernization/capture-performance-baseline.mjs --url=http://example.test --runs=5');
  process.exit(args.help ? 0 : 2);
}

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch {
  console.error('Playwright package is not installed. Install it in a local tooling workspace or run via npx playwright.');
  process.exit(2);
}

const runs = Number.parseInt(args.runs || '5', 10);
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await browser.newPage();
const results = [];
try {
  for (let i = 0; i < runs; i++) {
    const started = Date.now();
    const response = await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60000 });
    const duration = Date.now() - started;
    results.push({ run: i + 1, status: response?.status() ?? null, duration_ms: duration });
    console.log(`run ${i + 1}: ${duration}ms status=${response?.status() ?? 'n/a'}`);
  }
} finally {
  await browser.close();
}

const durations = results.map((result) => result.duration_ms).sort((a, b) => a - b);
const percentile = (p) => durations[Math.min(durations.length - 1, Math.floor((p / 100) * durations.length))];
console.log(JSON.stringify({
  url: args.url,
  runs,
  p50_ms: percentile(50),
  p95_ms: percentile(95),
  results,
}, null, 2));

