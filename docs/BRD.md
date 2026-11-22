# Gov Services Copilot — Business Requirements Document

## 1. Business Context

Government service officers consult regulations, policies, and procedural documents to understand a citizen's situation, identify applicable services and eligibility conditions, and provide accurate information about required documents, fees, and processing timelines.

Manual searching and cross-referencing can be time-consuming and may lead to inconsistent or unsupported interpretations. Gov Services Copilot is an AI-assisted decision-support system that retrieves evidence from an approved knowledge corpus, supports a structured government-service workflow, and drafts a response with traceable citations.

The system does not replace the officer's judgment. The officer reviews and approves the proposed response before it is submitted. When evidence is insufficient, ambiguous, or conflicting, the system must communicate uncertainty and request clarification or escalate rather than invent obligations or entitlements.

## 2. Stakeholders and Personas

### 2.1 Government Officer — Primary User

A government employee who handles citizen inquiries.

- Enters the citizen's situation.
- Reviews identified services, eligibility, procedures, documents, fees, and timelines.
- Reviews citations and uncertainty.
- Approves, rejects, or edits and approves the proposed response.
- Can access only cases they created.

### 2.2 System Administrator

Maintains users, roles, the knowledge base, and operational settings.

- Creates and manages user accounts and roles.
- Uploads and manages knowledge-base documents.
- Monitors document processing, token usage, and costs.
- Manages user budgets and permitted system configuration.

### 2.3 Citizen — External Stakeholder

The person seeking information about a government service. The citizen describes their situation to an officer; the officer enters it into the system. The citizen does not authenticate into the MVP.

**MVP boundary:** No citizen-facing portal or citizen account is included.

## 3. Business Objectives and Success Criteria

| ID    | Objective                               | Success criteria                                                                                                              |
| ----- | --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| BO-01 | Improve information retrieval           | Relevant chunks are retrieved and traceable to original sources.                                                              |
| BO-02 | Provide grounded guidance               | Claims have exact-chunk citations; insufficient, ambiguous, or conflicting evidence is surfaced rather than guessed.          |
| BO-03 | Support the government-service workflow | Three specialized agents and an orchestrator produce inspectable structured stage outputs.                                    |
| BO-04 | Preserve human control                  | An officer can approve, reject, or edit-and-approve; decisions are audited; submission requires approval.                     |
| BO-05 | Control AI costs                        | Per-user budgets, pre-flight estimates, budget-aware routing, hard cut-off, and administrative spend visibility are provided. |

## 4. Functional Requirements

### FR-01 — Document Ingestion

The system shall allow administrators to upload PDF and DOCX documents and process them through separate, testable stages: extract, clean, chunk, embed, and index.

**Acceptance criteria**

- PDF and DOCX are supported.
- Each stage can be tested independently.
- Document metadata includes source, version, and processing status.
- Chunk metadata includes source document, section, page or clause, and version where available.
- Identical files are detected by content hash and are not ingested twice.
- A changed document version remains separately identifiable; previous versions remain traceable.
- Per-document success/failure status and useful failure details are available.
- Only administrators can upload, update, or delete documents; authorization is enforced server-side.

### FR-02 — Information Retrieval

The system shall use hybrid retrieval: dense vector retrieval plus keyword retrieval, combined with a documented Reciprocal Rank Fusion (RRF) method. Metadata filtering is the selected retrieval enhancement.

**Acceptance criteria**

- Dense and keyword retrieval both operate.
- RRF fusion and the deliberate chunking strategy are documented.
- Metadata filtering is supported where applicable.
- Retrieved chunks preserve source, page/clause, section, and version metadata where available.
- Answers cite exact supporting chunks.
- Low-evidence questions are refused or returned as insufficient evidence.
- Ambiguous or conflicting sources are surfaced rather than silently guessed through.

### FR-03 — System Evaluation

The system shall provide a repeatable evaluation harness for retrieval quality, groundedness, and refusal correctness.

**Acceptance criteria**

- A golden set contains at least 25 Q/A cases, including at least five adversarial cases covering out-of-corpus, ambiguity, prompt injection, and conflicting sources.
- A runnable command executes the set.
- The harness reports retrieval hit rate, groundedness, and refusal correctness.
- Actual baseline results, including failures, are recorded and interpreted in `docs/EVALUATION.md`.

### FR-04 — Multi-Agent System

The system shall implement at least three specialized agents coordinated by an orchestrator.

| Agent                  | Responsibility                                                                       | Output                                                      |
| ---------------------- | ------------------------------------------------------------------------------------ | ----------------------------------------------------------- |
| Eligibility Identifier | Identify potentially applicable service and assess documented eligibility conditions | Service, eligibility status, missing information, citations |
| Procedure Resolver     | Resolve documented procedures for the selected service                               | Required documents, fees, steps, timelines, citations       |
| Response Drafter       | Draft a clear official response from validated prior outputs                         | Structured response draft, citations, uncertainties         |

The orchestrator validates outputs, controls progression, handles missing information and failures, and routes a completed draft to officer review.

**Minimum tools**

1. `SearchRegulationsTool` — read-only retrieval of chunks and sources.
2. `GetServiceRequirementsTool` — read-only retrieval of documented eligibility and document requirements.
3. `CalculateFeesAndTimelineTool` — deterministic calculation/retrieval using verified source data; no unsupported LLM arithmetic.
4. `SubmitOfficialResponseTool` — side-effecting submission, protected by backend approval and authorization checks.

**Acceptance criteria**

- At least three specialized agents and one orchestrator exist.
- Each agent has a defined role, typed input/output, termination conditions, and tool allowlist.
- At least four tools exist.
- At least one side-effecting tool is blocked until human approval.
- Typed contracts are validated; outputs preserve source references.
- Missing, ambiguous, conflicting, or invalid outputs do not lead to unsupported official responses.

### FR-05 — Workflow Orchestration

The system shall use **State Machine Orchestration** with predefined states and guarded transitions.

**Core states:** `RECEIVED`, `ELIGIBILITY_CHECK`, `PROCEDURE_RESOLUTION`, `RESPONSE_DRAFTING`, `NEEDS_INFORMATION`, `PENDING_APPROVAL`, `APPROVED`, `REJECTED`, `COMPLETED`, `FAILED`, `CANCELLED`.

**Acceptance criteria**

- The orchestrator enforces valid state transitions.
- Maximum iterations, per-step timeout, retry count, and backoff are centrally configurable.
- Retries apply to bounded temporary technical failures, not missing or conflicting evidence.
- Technical failure may degrade to Plain RAG only when retrieval remains available. The UI clearly says the multi-agent workflow did not complete; it shows retrieved information and citations only, with no unsupported eligibility or entitlement conclusion.
- Every run is inspectable by Run ID, including states, agents, tools, errors, retries, timing, and usage.
- Officer actions are approve, reject, and edit-and-approve; decisions are audited.
- Official submission is blocked until approval and authorization succeed.

### FR-06 — Real-Time Communication and Streaming

The system shall use Server-Sent Events (SSE) for live progress and token-level response streaming.

**Acceptance criteria**

- Token-level streaming is supported.
- Agent/workflow start, completion, failure, needs-information, cancellation, and completion events are associated with the Run ID.
- An officer can cancel an active workflow.
- Backend cancellation prevents subsequent steps and submission. The system attempts provider-level cancellation where supported; late results from cancelled calls cannot continue the workflow.
- A broken browser connection does not erase run state; status can be retrieved by Run ID.
- Provider cancellation limitations are documented honestly.

### FR-07 — User Interface and API

The system shall provide a minimal Blade + Livewire web interface and documented HTTP API (OpenAPI).

**Officer Workspace**

- Enter a citizen case and start a workflow.
- Monitor progress, inspect results and citations.
- Review, approve, reject, or edit-and-approve drafts.
- View own cases and their independent workflow runs.

**Admin Workspace**

- Manage users and roles.
- Upload/manage documents and view processing status.
- Manage system configuration and per-user budgets.
- View spend information.

**Acceptance criteria**

- The UI covers ingestion, asking with citations, workflow execution, approval, and trace inspection.
- Core API endpoints are documented with OpenAPI.
- Case and run history persist in the relational database.
- One case may have multiple independent runs; a new run never overwrites prior runs.
- Officers can access only cases they created; authorization is enforced server-side.

### FR-08 — Authentication and Access Control

The system shall use Laravel Fortify for authentication and support two roles.

- `officer`: create/manage own cases, execute workflows, search the knowledge base, and review/approve response drafts.
- `admin`: manage users, roles, documents, settings, budgets, and administrative spend view.

**Acceptance criteria**

- Login/logout are provided through Fortify.
- Public registration is disabled; administrators create accounts.
- Passwords are securely hashed.
- Middleware and Policies enforce role permissions and case ownership on the server.
- Officers cannot manage documents or access other officers' cases.
- Unauthorized API and UI actions are rejected.

### FR-09 — Observability and Cost Monitoring

The system shall trace workflow execution and account for AI token usage and costs.

**Acceptance criteria**

- Each incoming request receives a Correlation ID propagated through orchestrator, agents, tools, and LLM calls.
- Each workflow has an inspectable Run ID.
- Workflow states, agent/tool calls, retrieved chunk references, durations, and errors are recorded.
- A custom MySQL trace store records provider/model, agent, Run ID, token counts where available, duration, status, and error details for each AI call.
- Token usage is attributable to user, run, agent, provider, and model.
- Costs use configurable provider/model pricing; estimated and actual usage are distinguished where possible.
- Usage/cost data is persisted and queryable.
- Admin-only Spend View supports basic breakdowns by user, run, agent, provider, and model.
- Health and readiness endpoints exist; readiness checks required dependencies including MySQL and Qdrant.

## 5. Business Rules

- **BR-01 — Evidence first:** A factual claim about a service, eligibility, obligation, document, fee, or timeline must be supported by retrieved approved-corpus evidence.
- **BR-02 — No unsupported entitlement:** When evidence is insufficient, ambiguous, or conflicting, the system must not infer an obligation or entitlement; it must ask for clarification or escalate.
- **BR-03 — Officer approval:** A drafted response is not an official response until an authorized officer explicitly approves it.
- **BR-04 — Approval before side effect:** The submission tool must independently verify approval and authorization at execution time.
- **BR-05 — Version traceability:** Citations must identify the document version used; a new version must not erase historical traceability.
- **BR-06 — Case ownership:** Officers may access only cases they created.
- **BR-07 — Admin-only knowledge management:** Only administrators may add, update, or delete knowledge-base documents.
- **BR-08 — No duplicate ingestion:** Byte-identical file uploads are detected by content hash.
- **BR-09 — Safe degradation:** Plain RAG fallback must be labeled as fallback and must not claim that the multi-agent workflow completed.
- **BR-10 — Cost guard:** A workflow must not continue once the applicable hard token budget is reached.
- **BR-11 — Deterministic calculations:** Fees and timelines must be retrieved or calculated using documented rules and deterministic code, not invented by an LLM.
- **BR-12 — Synthetic/public corpus only:** The repository and demo corpus must not contain real personal data.

## 6. Out of Scope for the MVP

- Citizen-facing portal, citizen accounts, and direct citizen submissions.
- Multiple organizations/tenant isolation.
- Full human-review queue with assignment, SLA timers, escalation, and reviewer statistics.
- Offline-first operation or local-model runtime as the primary path.
- OCR for scanned documents unless required by the selected corpus and time permits.
- Generated official DOCX/PDF output.
- Microservices, Kubernetes, autoscaling, and multi-region deployment.
- Advanced analytics, notifications, and a visually elaborate dashboard.
- Production integration with real government systems or real legal decision-making.

## 7. Assumptions and Constraints

- The assigned variant is Government (D4) with Cost Governor (T3).
- An officer enters the citizen's situation; citizens do not use the application directly.
- The corpus contains at least 30 public or synthetic documents and 150+ pages; no real personal data is used.
- Stack: Laravel 13, PHP, Blade, Livewire, MySQL, Qdrant, Docker Compose; hosted providers planned: Groq and Gemini.
- Laravel Fortify is the authentication backend.
- The application is a modular monolith; agents are components within the application, not separate deployed services.
- The MVP is time-boxed to approximately 40 focused hours / 12 calendar days.
- Provider capabilities, free-tier limits, token reporting, streaming, and cancellation must be verified during implementation; unsupported capabilities must be documented.
- Model pricing and per-user budgets are configuration-driven, not hard-coded.

## 8. Risks and Mitigations

| Risk                                     | Impact      | Mitigation                                                                            |
| ---------------------------------------- | ----------- | ------------------------------------------------------------------------------------- |
| Unsupported eligibility/obligation claim | High        | Evidence rules, refusal/escalation, exact citations, officer approval                 |
| Prompt injection in source documents     | High        | Separate instructions from retrieved content, tool allowlists, adversarial evaluation |
| Provider outage or free-tier exhaustion  | Medium/High | Second provider, bounded fallback, hard budget limits, honest degradation             |
| Incorrect/stale document version         | High        | Version metadata, immutable historical references, version-aware retrieval            |
| Token cost overrun                       | High        | Pre-flight estimate, per-user budget, routing, hard cut-off, persisted spend          |
| Cross-user case access                   | High        | Server-side Policies and ownership tests                                              |
| Incomplete cancellation                  | Medium      | Backend cancellation state, prevent later steps, discard late provider result         |
| Tight delivery window                    | High        | Minimal UI, mandatory requirements first, document deferred scope in SDD              |

## 9. Requirements Traceability Matrix

Update the status and evidence columns only after implementation and verification.

| Requirement | Planned evidence                                                     | Status      |
| ----------- | -------------------------------------------------------------------- | ----------- |
| FR-01       | Ingestion tests, document status UI, metadata/version records        | Not started |
| FR-02       | Retrieval integration tests, citation examples, retrieval ADR        | Not started |
| FR-03       | Golden dataset, runnable harness, `docs/EVALUATION.md` results       | Not started |
| FR-04       | Agent/tool contracts, allowlists, contract tests                     | Not started |
| FR-05       | State-machine tests, approval audit, Run ID trace                    | Not started |
| FR-06       | SSE demo, cancellation test, provider limitations note               | Not started |
| FR-07       | UI walkthrough, OpenAPI document, ownership tests                    | Not started |
| FR-08       | Fortify auth flow, role/Policy tests                                 | Not started |
| FR-09       | Trace records, cost query, admin spend view, health/readiness checks | Not started |
