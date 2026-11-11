#!/usr/bin/env node

// Browser smoke test of the range pickers against the running workbench (`composer serve`).

import { chromium } from 'playwright';

const baseUrl = process.env.WORKBENCH_URL ?? 'http://127.0.0.1:8766';

const browser = await chromium.launch();
const page = await (await browser.newContext({ viewport: { width: 1280, height: 1400 } })).newPage();
const fail = (message) => { console.error(`FAIL ${message}`); process.exitCode = 1; };
const check = (condition, message) => (condition ? console.log(`ok   ${message}`) : fail(message));

await page.goto(`${baseUrl}/dev-login/${encodeURIComponent('salman@example.com')}`);
await page.goto(`${baseUrl}/admin/deliveries`);
await page.waitForLoadState('networkidle');

// Compact: From, then the panel moves to To on its own.
const holiday = page.locator('[data-demo="date-range-compact"] .fi-chrono').first();
await holiday.locator('.fi-chrono-range-half').first().click();

const fromPanel = holiday.locator('[data-chrono-end="start"]');
const toPanel = holiday.locator('[data-chrono-end="end"]');

await fromPanel.waitFor({ state: 'visible' });
check(await toPanel.isHidden(), 'compact opens on From with To hidden');

await fromPanel.locator('.fi-chrono-calendar-day', { hasText: /^10$/ }).click();
await toPanel.waitFor({ state: 'visible' });
check(await fromPanel.isHidden(), 'picking From moves the panel to To');

// To still shows its own month, so step back to December.
await toPanel.getByRole('button', { name: 'Previous month' }).click();
check(await toPanel.locator('.fi-chrono-calendar-day', { hasText: /^9$/ }).evaluate((el) => el.classList.contains('fi-chrono-calendar-day-disabled')), 'To dims the days before From');

await toPanel.locator('.fi-chrono-calendar-day', { hasText: /^20$/ }).click();
await holiday.getByRole('button', { name: 'OK' }).click();

const text = (await holiday.locator('.fi-chrono-range-field').innerText()).replace(/\s+/g, ' ');
check(/Dec 10, \d{4} – Dec 20, \d{4}/.test(text), `the field shows the range (${text})`);

// Clicking the To half reopens on To.
await holiday.locator('.fi-chrono-range-half').nth(1).click();
await toPanel.waitFor({ state: 'visible' });
check(await fromPanel.isHidden(), 'the To half of the text reopens To');
await holiday.getByRole('button', { name: 'Cancel' }).click();

// A month range, expanded.
const contract = page.locator('[data-demo="month-range"] .fi-chrono').first();
await contract.locator('.fi-chrono-range-half').first().click();
await contract.locator('[data-chrono-end="start"] .fi-chrono-calendar-cell', { hasText: /^Mar$/ }).first().click();
await contract.locator('[data-chrono-end="end"] [x-show="view === \'months\'"] .fi-chrono-calendar-cell', { hasText: /^Sep$/ }).click();
await contract.getByRole('button', { name: 'OK' }).click();

// Saving the page: the notification carries the dehydrated state.
await page.getByRole('button', { name: 'Save' }).first().click();
const body = await page.locator('.fi-no-notification').first().innerText();

check(/"holiday":\{"from":"\d{4}-12-10","to":"\d{4}-12-20"\}/.test(body.replace(/\\\//g, '/')), 'the JSON range saves under its own keys');
check(/"contract_starts":"\d{4}-03","contract_ends":"\d{4}-09"/.test(body), 'the month range saves into its two columns as Y-m');
check(! /"contract":/.test(body) && ! /"delivery_dates":/.test(body), 'a range stored in columns leaves no key of its own');

await browser.close();
