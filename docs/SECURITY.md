# Gov Services Copilot — Security

**Status:** Planned — controls documented, implementation pending  
**Required by:** ITI-task.md §6, BRD §8 (Security Requirements)  
**Date:** 2026-11-22

---

## 1. Purpose and Scope

This document maps security controls to threats for Gov Services Copilot. It covers web application security, LLM application security, and operational security for the MVP.

All controls listed are planned for the MVP. None claim to be fully implemented or tested unless explicitly stated.

---

## 2. Web Application Security

### 2.1 Authentication

| Control            | Description                                                                                                                                                        | Requirement Source      |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------- |
| Laravel Fortify    | Login/logout via Fortify. Public registration disabled; administrators create accounts. Password reset enabled. Email verification disabled. Remember Me optional. | ITI §8, FR-08           |
| Password hashing   | Passwords hashed via Laravel's bcrypt implementation.                                                                                                              | ITI §8                  |
| Remember tokens    | Fortify remember-me functionality for persistent sessions (optional).                                                                                              | Laravel Fortify default |
| Email verification | Disabled for MVP. Not enabled by default.                                                                                                                          | Fortify configuration   |

### 2.2 Authorization and Access Control

| Control              | Description                                                                                                                                                                                                                                                                                                                                                           | Requirement Source   |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- |
| Role-based access    | Two roles: `officer` and `admin`, stored on `users.role`. Enforced via Laravel Policies and middleware.                                                                                                                                                                                                                                                               | ITI FR-08, BRD §8    |
| Case ownership       | Officers can access only cases they created (`cases.user_id`). Server-side ownership test on every case access.                                                                                                                                                                                                                                                       | ITI BR-06, FR-07     |
| Admin scope          | Admins can manage users, roles, documents, settings, budgets, and view spend.                                                                                                                                                                                                                                                                                         | ITI FR-07, BRD §2.2  |
| Document management  | Only administrators may upload, update, or delete knowledge-base documents. Enforced server-side.                                                                                                                                                                                                                                                                     | ITI FR-01, BR-07     |
| Evaluation Case      | Single system-managed case referenced by all evaluation runs (`run_type = 'evaluation'`). Inaccessible via UI and API to ordinary officers. Does not appear in normal case listings and is not editable by officers. Evaluation access is restricted to the evaluation harness/system authorization path. Enforced at the Application Layer via server-side Policies. | ITI FR-03, BRD FR-03 |
| Unauthorized actions | Unauthorized API and UI actions are rejected with 403.                                                                                                                                                                                                                                                                                                                | ITI FR-08            |

### 2.3 Cryptographic Failures

| Control           | Description                                                                                               |
| ----------------- | --------------------------------------------------------------------------------------------------------- |
| HTTPS only        | Production deployment must use TLS. Local development uses HTTP via Docker.                               |
| Secrets in `.env` | API keys, database credentials, and session secrets stored in `.env`, never committed to Git.             |
| APP_KEY           | Laravel application key generated via `php artisan key:generate`. Used for session and cookie encryption. |

### 2.4 Injection

| Control           | Description                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------------- |
| SQL injection     | Laravel Eloquent/query builder uses parameterized bindings. No raw SQL with user input.                           |
| XSS               | Blade templates escape output by default (`{{ }}`). Raw output (`{!! !!}`) is avoided for user-generated content. |
| Command injection | No shell execution of user input. Document parsers operate on uploaded files only.                                |

### 2.5 Validated Uploads

| Control               | Description                                                                                                                  |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| File type restriction | Only PDF and DOCX accepted (FR-01). Validated by MIME/content type and extension server-side.                                |
| File size limit       | PHP `upload_max_filesize` and Laravel validation limit.                                                                      |
| Safe storage          | Uploaded files stored in private storage with server-generated filenames. Original filenames are not used for storage paths. |
| Parsing protection    | Malformed or resource-intensive files are bounded during extraction/cleaning to prevent denial-of-service.                   |
| Content hash          | `content_hash` on `document_versions` for duplicate detection (BR-08).                                                       |
| Virus scanning        | Not included in MVP. Deferred to production deployment.                                                                      |

### 2.6 Rate Limits and Abuse

| Control             | Description                                                                                |
| ------------------- | ------------------------------------------------------------------------------------------ |
| Login rate limiting | Request rate limits applied to login/authentication attempts to prevent brute-force.       |
| Workflow execution  | Rate limits on workflow creation/execution per user.                                       |
| Document uploads    | Rate limits on document upload endpoints per user.                                         |
| Sensitive API       | Rate limits on approval, submission, and other sensitive API endpoints.                    |
| Token budgets       | Per-user token budgets with hard cut-off (BR-10, T3). Separate from request rate limiting. |
| Workflow iterations | Max iterations centrally configurable (`WORKFLOW_MAX_ITERATIONS`).                         |
| Step timeout        | Per-step timeout centrally configurable (`WORKFLOW_STEP_TIMEOUT`).                         |
| Retry count         | Max retries centrally configurable (`WORKFLOW_MAX_RETRIES`).                               |

### 2.7 Secure Configuration

| Control              | Description                                                                                                                                                                                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `.env` not committed | `.env` is in `.gitignore`. `.env.example` provided without secrets.                                                                                                                                                       |
| APP_DEBUG            | Set to `false` in production.                                                                                                                                                                                             |
| CORS                 | Not applicable for MVP (Blade/Livewire server-rendered UI, no separate SPA).                                                                                                                                              |
| Security headers     | Application explicitly configures `X-Content-Type-Options`, `X-Frame-Options`, and `Referrer-Policy`. Content Security Policy (CSP) is a policy to configure and verify for the actual UI; not pre-configured by default. |

---

## 3. LLM Application Security

### 3.1 Prompt Injection

| Control                           | Description                                                                                                                 |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| System vs user content separation | System instructions kept separate from untrusted retrieved content. Retrieved chunks are treated as data, not instructions. |
| Tool allowlists                   | Each agent has a defined tool allowlist. Agents cannot call tools outside their allowlist.                                  |
| Tool argument validation          | Tool arguments validated against typed schemas before execution.                                                            |
| Adversarial evaluation            | At least 3 adversarial prompt-injection cases in the golden/evaluation set (ITI §6, FR-3).                                  |

### 3.2 Untrusted Content Handling

| Control                | Description                                                                                                        |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Uploaded document text | Parsed, validated, and marked as untrusted data. Never executed as instructions.                                   |
| Retrieved chunks       | Treated as untrusted data, never privileged instructions. Citations preserved but content is not blindly executed. |
| LLM output             | Untrusted until schema-validated and business-rule checked.                                                        |
| Raw output passthrough | No model output passed directly to SQL, shell, raw HTML, or filesystem paths.                                      |

### 3.3 Side-Effecting Actions

| Control                 | Description                                                                                                                                                                                    |
| ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Submission tool gated   | `SubmitOfficialResponseTool` is blocked until human approval and authorization succeed (ITI FR-04, BR-03, BR-04).                                                                              |
| Approval verification   | Submission tool independently verifies ownership and authorization at execution time. Only the officer who owns the case (or an admin) may approve and execute submission.                     |
| No automatic submission | Draft responses are not submitted without explicit officer approval.                                                                                                                           |
| External provider trust | Prompts and retrieved chunks are sent to hosted AI providers (Groq, Gemini). This is documented as a trust boundary. Provider data-retention policies must be reviewed before use.             |
| Budget cut-off          | Hard token cut-off prevents subsequent LLM calls once the budget is exhausted, but cannot guarantee zero overage from an already-running provider call initiated before the limit was reached. |

### 3.4 PII and Data Handling

| Control                     | Description                                                                                                          |
| --------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Synthetic corpus only       | Repository and demo corpus must not contain real personal data (ITI BR-12).                                          |
| Citizen data                | Citizens do not authenticate. Officers enter citizen situations on their behalf. No citizen-facing portal.           |
| Data leaving infrastructure | Prompts and retrieved chunks are sent to hosted AI providers (Groq, Gemini). This is documented as a trust boundary. |

---

## 4. Audit and Observability

| Control             | Description                                                                                                                            |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Correlation IDs     | Each incoming request receives a Correlation ID propagated through orchestrator, agents, tools, and LLM calls (ITI FR-09).             |
| Audit logs          | `audit_logs` table records security-relevant events: document upload, budget exceeded, approval decisions, user management.            |
| Audit log hygiene   | Passwords, API keys, session secrets, and unnecessary sensitive personal data are prohibited from being logged.                        |
| LLM trace store     | `llm_calls` custom MySQL trace store records provider/model, agent, Run ID, token counts, duration, status, error details (ITI FR-09). |
| Workflow inspection | Every run is inspectable by Run ID, including states, agents, tools, errors, retries, timing, and usage (ITI FR-05).                   |

---

## 5. Operational Security

| Control             | Description                                                                                                                                          |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Docker Compose      | MySQL, Qdrant, and the application are exposed on host ports: MySQL 3306, application 8000, Qdrant 6333. All three services accept host connections. |
| Secrets             | API keys in `.env`. No committed secrets. Full-history secret scan before submission (ITI §11).                                                      |
| Dependency scanning | Not configured in MVP CI. Deferred to production deployment.                                                                                         |
| Backups             | Basic backup/restore instructions documented. No automated restore in MVP.                                                                           |

---

## 6. Threat Model Summary

| Threat                                    | Asset                                | Impact      | Mitigation                                                                            |
| ----------------------------------------- | ------------------------------------ | ----------- | ------------------------------------------------------------------------------------- |
| Unsupported eligibility/entitlement claim | Citizen trust, legal compliance      | High        | Evidence rules, refusal/escalation, exact citations, officer approval                 |
| Prompt injection in source documents      | Model behavior, output integrity     | High        | Separate instructions from retrieved content, tool allowlists, adversarial evaluation |
| Provider outage or free-tier exhaustion   | Service availability                 | Medium/High | Second provider, bounded fallback, hard budget limits, honest degradation             |
| Incorrect/stale document version          | Citation integrity, legal compliance | High        | Version metadata, immutable historical references, version-aware retrieval            |
| Token cost overrun                        | Budget exhaustion                    | High        | Pre-flight estimate, per-user budget, routing, hard cut-off, persisted spend          |
| Cross-user case access                    | Data privacy                         | High        | Server-side Policies and ownership tests                                              |
| Incomplete cancellation                   | Resource waste, incorrect results    | Medium      | Backend cancellation state, prevent later steps, discard late provider result         |

---

## 7. Controls Not Included in MVP

| Control                   | Reason                                  | Deferred To           |
| ------------------------- | --------------------------------------- | --------------------- |
| MFA                       | Not required for MVP scope              | Production deployment |
| Virus scanning on uploads | Additional infrastructure               | Production deployment |
| WAF / API gateway         | Not required for local MVP              | Production deployment |
| Secrets manager           | `.env` sufficient for local Docker      | Production deployment |
| Dependency scanning in CI | Not configured yet                      | CI pipeline setup     |
| Encryption at rest        | Docker volume encryption not configured | Production deployment |
