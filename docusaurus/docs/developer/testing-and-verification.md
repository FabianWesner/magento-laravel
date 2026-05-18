---
id: testing-and-verification
title: Testing And Verification
---

Verification must prove production readiness, not only happy-path behavior.

## Required Test Coverage

- Unit, integration, characterization, dual-runtime parity, browser, Livewire, API contract, scheduler, queue, visual, accessibility, security, performance, and operations tests.
- Normal, edge, failure, invalid input, permission-denied, timeout, retry, race-condition, stale-cache, stale-index, missing-media, integration-outage, and rollback scenarios.
- Database side effects for cart, checkout, order, payment, tax, shipping, refund, reports, indexes, and cron.

## Done Rule

The system is done only when the full test plan in `specs/modernization/test-plan.md` passes and every feature ID has retained evidence.
