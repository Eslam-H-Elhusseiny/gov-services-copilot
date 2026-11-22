# Gov Services Copilot — System Design Document

## 1. Purpose and Scope

This document separates the unconstrained target architecture from the time-boxed MVP that will be implemented for the assessment. The target is a teaching/design reference, not a claim that all components exist.

## 2. Part A — Unconstrained Target Architecture

A production-scale version could include:

| Capability | Target design | Purpose |
|---|---|---|
| Edge/API gateway | Managed API gateway with WAF and rate limiting | Authentication boundary, abuse protection, routing |
| Identity | Central identity provider with MFA and managed secrets | Strong authentication and secret rotation |
| Application | Horizontally scalable stateless application services | Independent scaling of UI/API/workflow |
| Workflow execution | Durable message broker and worker pool | Long-running, retryable workflows |
| Relational data | Managed relational database with backups and point-in-time recovery | Cases, users, audit, configuration |
| Vector retrieval | Managed vector database with snapshots/replicas | Semantic retrieval at scale |
| Keyword retrieval | Relational/search service with indexed text | Exact phrase and keyword retrieval |
| Cache | Managed cache | Frequently accessed metadata and rate-limit counters |
| AI providers | Provider gateway/adapter layer with routing and fallback | Resilience, model choice, cost control |
| Observability | Central logs, traces, metrics, dashboards, alerting | End-to-end operations |
| CI/CD | Isolated dev/staging/prod environments with automated promotion | Repeatable delivery |
| DR/backup | Tested backup, restore, and disaster-recovery objectives | Recovery from data/service loss |
| Cost management | Per-user quotas, budgets, model routing, spend alerts | Prevent uncontrolled AI spend |

### Target operational considerations

- Define RPO/RTO and test restore procedures.
- Separate secrets from application configuration and rotate them.
- Apply gateway-level rate limits and payload limits.
- Use durable queues for long-running workflows.
- Scale workers independently from web requests.
- Monitor provider latency, error rate, token usage, and spend.
- Use least-privilege service identities and network boundaries.
- Keep an auditable approval trail for consequential actions.

## 3. Part B — Implemented MVP Design

### 3.1 Selected Stack

| Layer | MVP choice | Rationale |
|---|---|---|
| Backend | Laravel 13 / PHP | User's selected stack and integrated web/API framework |
| UI | Blade + Livewire | Minimal server-driven UI; no separate SPA |
| Authentication | Laravel Fortify | Authentication backend with custom role authorization |
| Relational store | MySQL | Cases, users, document metadata, traces, budgets, audit |
| Vector store | Qdrant | Dense vector retrieval |
| Keyword search | MySQL Full-Text Search | Avoid a separate search service in MVP |
| Queue | Laravel database queue + worker container | Avoid adding Redis as a required service |
| Runtime | Docker Compose | Reproducible setup on another Docker-capable machine |
| AI providers | Groq and Gemini planned | Two hosted implementations; verify required capabilities |
| AI integration | Laravel AI SDK where suitable, behind application-owned interfaces | Use framework capabilities without leaking SDK types into core layers |

### 3.2 Logical Components

- **Presentation:** Blade/Livewire screens, HTTP controllers, SSE endpoint, middleware.
- **Application:** use cases for ingestion, retrieval, case workflow, approval, budget checks, and reporting.
- **Domain:** case/workflow states, typed contracts, approval rules, budget rules, domain errors.
- **Infrastructure:** Eloquent repositories, MySQL, Qdrant adapter, document parsers, provider adapters, queue, trace persistence.

Domain and application code must not depend on Laravel, an LLM SDK, or the Qdrant SDK. External systems are accessed through interfaces owned by the application/core.

### 3.3 Workflow

1. Officer creates a case and submits the citizen's situation.
2. System checks user budget and estimates likely token consumption.
3. Orchestrator creates a Run ID and starts the state machine.
4. Eligibility Identifier retrieves and evaluates supporting evidence.
5. If required information is missing or evidence is ambiguous/conflicting, transition to `NEEDS_INFORMATION` or stop/escalate.
6. Procedure Resolver identifies documented documents, fees, steps, and timelines.
7. Response Drafter produces a structured draft with citations.
8. Officer reviews, rejects, edits, or approves.
9. Backend verifies approval and authorization before invoking the submission side effect.
10. Usage, cost, trace, and audit records remain queryable.

### 3.4 T3 Cost Governor

The MVP must demonstrate all twist behaviors:

- Per-user token budgets with configurable limits and reset period.
- Pre-flight token/cost estimation before workflow execution.
- Budget-aware routing: cheaper configured model for eligible simple steps; stronger model only when policy requires it.
- Hard cut-off when the user's budget is exhausted.
- Persisted spend view for administrators.

Use provider/model pricing configuration with an effective date. Store estimates separately from provider-reported actual usage. If a provider does not return usage, mark it unavailable/estimated rather than fabricating exact usage. Define behavior when a provider does not support a required feature.

### 3.5 Provider Abstraction

One application-owned provider contract must cover:
- Completion.
- Token-level streaming.
- Tool/function calling.
- Embeddings.

At least two working implementations are mandatory. Groq and Gemini are the planned hosted providers, but do not mark the requirement complete until each required operation is verified or a documented capability strategy is implemented. Embedding support must also satisfy the abstraction requirement; it cannot silently be available from only one implementation if the assessment expects both implementations to cover the interface. If a provider lacks a capability, expose that limitation explicitly and define routing/fallback behavior without changing business logic.

### 3.6 Data Stores

**MySQL** stores users, roles, cases, workflow runs, approvals, document metadata/versions, chunk metadata and text as appropriate, budgets, provider pricing configuration, token usage, traces, and audit events.

**Qdrant** stores dense vectors with stable chunk/document identifiers and filterable metadata. MySQL remains the source of truth for relational ownership, document version, and audit state.

Migrations must create relational schema and Qdrant collection setup must be repeatable/documented.

### 3.7 Packaging

`docker compose up` should start the web application, MySQL, Qdrant, and required worker process(es). Provide a seed/ingest command, complete `.env.example`, and no committed secrets. Confirm clean-machine startup before submission.

## 4. Gap Table — Target vs MVP

Update this table with actual status after implementation. “Planned” is not “implemented”.

| Target component | MVP implementation | Why deferred / limitation | Interim mitigation | Effort/cost to close |
|---|---|---|---|---|
| Managed API gateway/WAF | Not planned for local MVP | Time and no production deployment requirement | Laravel middleware, validation, throttling | Estimate after deployment target selected |
| Managed secrets manager | Not included | Local Docker assessment scope | `.env` outside Git; document production secret-manager need | Environment-dependent |
| Durable broker | Database queue | Fewer moving parts | Worker container; bounded retries | Replace queue driver and provision broker |
| Autoscaling | Not included | No production load target | Single Compose deployment | Requires deployment platform and load testing |
| Managed relational DB | MySQL container | Portable local demo | Backups/export instructions | Provision managed DB and test restore |
| Managed vector DB | Qdrant container | Portable local demo | Persistent Docker volume and backup notes | Managed Qdrant plan/hosting |
| Distributed cache | Not included | Avoid extra service | DB-backed mechanisms where sufficient | Add only after measured need |
| Central observability platform | MySQL custom trace store + structured logs | Small MVP footprint | Correlation IDs and inspectable run records | Add OTel collector/backend and dashboards |
| Multi-environment CI/CD | GitHub Actions CI; deployment may be deferred | 12-day constraint | Green CI and documented manual release | Add staging/prod environments |
| DR/automated restore | Basic backup/restore instructions | No production SLA | Document manual backup and restore | Define RPO/RTO and automate |
| Advanced spend analytics | Basic admin spend view | Scope control | Per-user/run/provider/model totals | Add alerts and trend dashboards |

## 5. Significant Decisions and Alternatives

See `docs/adr/` for the required Architecture Decision Records:
- ADR-001: Chunking and hybrid retrieval.
- ADR-002: State machine orchestration.
- ADR-003: Qdrant as vector store.
- ADR-004: Cost Governor design.

Additional decisions to record if implementation materially changes: provider selection, queue driver, trace storage, and SSE/cancellation implementation.

## 6. Known Risks and Honest Trade-offs

- Free-tier limits and provider capabilities can change; test and record the date/configuration used.
- Token accounting may be exact only when providers report usage. Estimated values must be labeled.
- Provider cancellation may not be supported for every in-flight request. Backend cancellation must still prevent late results from advancing the workflow.
- MySQL custom tracing is inspectable but is not a substitute for a full distributed observability platform.
- A single Compose deployment is for assessment portability, not a claim of high availability.
- Synthetic government documents must be clearly labeled as fictional and must not be presented as real Egyptian law.
