# ADR-004 — Cost Governor (T3)

- **Status:** Accepted as MVP design; values/provider behavior must be verified.
- **Date:** 2026-11-21

## Context

The assigned twist requires per-user token budgets, budget-aware model routing, pre-flight estimation, hard cut-off, and spend view.

## Decision

Implement a centralized budget service called before workflow execution and before provider calls. Store per-user budgets and provider/model pricing in configuration or relational settings. Estimate tokens/cost before execution, route eligible simple tasks to a cheaper configured model, record actual provider-reported usage when available, and stop further calls when the budget is exhausted. Provide an admin-only spend view.

## Alternatives Considered

- Hard-coded global limit: rejected because limits need configuration and per-user attribution.
- Track cost only after completion: rejected because it cannot enforce a pre-flight or hard budget.
- Add a separate billing service: rejected for MVP complexity.

## Consequences

- Estimation is approximate; report it as an estimate.
- Actual usage depends on provider telemetry; missing usage must remain marked unavailable.
- Pricing needs a source/effective date and must be updated when provider pricing changes.
- Hard cut-off must be enforced server-side, not only in the UI.
