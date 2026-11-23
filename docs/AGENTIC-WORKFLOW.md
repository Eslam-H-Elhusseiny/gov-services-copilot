# Gov Services Copilot — Agentic Workflow

**Status:** Design documented — implementation pending  
**Required by:** ITI-task.md FR-4, FR-5, FR-6, ADR-002  
**Date:** 2026-11-22

---

## 1. Purpose and Scope

This document documents the agentic workflow design for Gov Services Copilot: the orchestrator, the three specialized agents, their tools, the state machine, and the approval lifecycle. It is consistent with `docs/BRD.md`, `docs/SYSTEM-DESIGN.md`, `docs/ARCHITECTURE.md`, and `docs/adr/ADR-002`.

No agent code has been implemented yet. This document describes the intended design.

---

## 2. Orchestration Pattern

### 2.1 State Machine Orchestrator

The orchestrator uses a **deterministic State Machine pattern** (ADR-002). It controls named states and guarded transitions. Specialized agents perform bounded tasks and return typed contracts. The orchestrator does not delegate routing decisions to an autonomous supervisor.

**Core states (from BRD FR-05):**

| State                  | Meaning                                          |
| ---------------------- | ------------------------------------------------ |
| `RECEIVED`             | Case created, workflow initialized               |
| `ELIGIBILITY_CHECK`    | Eligibility Identifier agent running             |
| `PROCEDURE_RESOLUTION` | Procedure Resolver agent running                 |
| `RESPONSE_DRAFTING`    | Response Drafter agent running                   |
| `NEEDS_INFORMATION`    | Missing information — paused for officer input   |
| `PENDING_APPROVAL`     | Draft ready for officer review                   |
| `APPROVED`             | Officer approved the draft; ready for submission |
| `REJECTED`             | Officer rejected the draft; terminal state       |
| `COMPLETED`            | Submission succeeded; final terminal state       |
| `FAILED`               | Technical failure — workflow stopped             |
| `CANCELLED`            | Officer or system cancelled the workflow         |

### 2.2 Guarded Transitions

The orchestrator enforces valid state transitions. Invalid transitions are rejected. Transitions are triggered by:

- Agent step completion (success or failure)
- Officer approval/rejection decision
- Cancellation request
- Timeout or iteration limit exceeded

### 2.3 Configuration

| Setting                    | Environment Variable      | Default |
| -------------------------- | ------------------------- | ------- |
| Maximum iterations         | `WORKFLOW_MAX_ITERATIONS` | 6       |
| Per-step timeout (seconds) | `WORKFLOW_STEP_TIMEOUT`   | 60      |
| Maximum retries            | `WORKFLOW_MAX_RETRIES`    | 2       |

### 2.4 Retry Policy

- Retries apply to **bounded temporary technical failures only** (e.g., provider timeout, transient network error).
- Retries do **not** apply to missing, ambiguous, or conflicting evidence.
- Retries do **not** apply to the side-effecting submission tool (`SubmitOfficialResponseTool`) to prevent duplicate submissions.
- Retry count is tracked per step in `workflow_steps.retry_count`.
- Backoff is applied between retries (implementation detail).

### 2.5 Graceful Degradation

If the multi-agent workflow fails due to technical issues but retrieval remains available, the system degrades to **Plain RAG only**. Plain RAG fallback continues within the same workflow run and retains the same Run ID. The multi-agent technical failure and fallback steps are recorded in the existing workflow trace (`workflow_steps`). The UI clearly labels the result as degraded / Plain RAG and indicates that the multi-agent workflow did not complete. Plain RAG shows retrieved information and citations only, with no unsupported eligibility or entitlement conclusion (BR-09). No separate workflow run is created for fallback.

---

## 3. Specialized Agents

Three specialized agents are coordinated by the orchestrator. Each agent has a defined role, typed input/output, termination conditions, and tool allowlist.

### 3.1 Eligibility Identifier

| Attribute      | Value                                                                                |
| -------------- | ------------------------------------------------------------------------------------ |
| Responsibility | Identify potentially applicable service and assess documented eligibility conditions |
| Output         | Service, eligibility status, missing information, citations                          |
| Tools          | `SearchRegulationsTool`, `GetServiceRequirementsTool`                                |
| Termination    | Returns eligibility result or transitions to `NEEDS_INFORMATION`                     |

### 3.2 Procedure Resolver

| Attribute      | Value                                                                                 |
| -------------- | ------------------------------------------------------------------------------------- |
| Responsibility | Resolve documented procedures for the selected service                                |
| Output         | Required documents, fees, steps, timelines, citations                                 |
| Tools          | `SearchRegulationsTool`, `GetServiceRequirementsTool`, `CalculateFeesAndTimelineTool` |
| Termination    | Returns procedure result or transitions to `NEEDS_INFORMATION`                        |

### 3.3 Response Drafter

| Attribute      | Value                                                        |
| -------------- | ------------------------------------------------------------ |
| Responsibility | Draft a clear official response from validated prior outputs |
| Output         | Structured response draft, citations, uncertainties          |
| Tools          | `SearchRegulationsTool` (read-only)                          |
| Termination    | Returns draft response and transitions to `PENDING_APPROVAL` |

---

## 4. Tools

At least four tools exist. Each tool is read-only except the submission tool, which is side-effecting and gated by approval.

| Tool                           | Type           | Description                                                                                   | Approval Required |
| ------------------------------ | -------------- | --------------------------------------------------------------------------------------------- | ----------------- |
| `SearchRegulationsTool`        | Read-only      | Retrieval of chunks and sources from the knowledge corpus                                     | No                |
| `GetServiceRequirementsTool`   | Read-only      | Retrieval of documented eligibility and document requirements                                 | No                |
| `CalculateFeesAndTimelineTool` | Read-only      | Deterministic calculation/retrieval using verified source data; no unsupported LLM arithmetic | No                |
| `SubmitOfficialResponseTool`   | Side-effecting | Submission of the approved official response                                                  | Yes (BR-04)       |

### 4.1 Tool Contracts

Each tool has a typed input/output contract. Tool arguments are validated against schemas before execution. Tool results are recorded in `workflow_steps.output` as JSON.

---

## 5. Workflow Lifecycle

### 5.1 Sequence

1. Officer creates a case and submits the citizen's situation (`cases.situation`).
2. Token estimation happens first to estimate likely consumption.
3. Budget locking, validation, token reservation, and `workflow_runs` creation happen in the same MySQL transaction. The transaction creates the run with `run_uuid`, `run_type = 'officer'`, `estimated_tokens`, and `reserved_tokens`.
4. The orchestrator starts processing only after the transaction commits.
5. Orchestrator starts the state machine at `RECEIVED`.
6. Eligibility Identifier retrieves and evaluates supporting evidence.
7. If required information is missing or evidence is ambiguous/conflicting, transition to `NEEDS_INFORMATION` or stop/escalate.
8. Procedure Resolver identifies documented documents, fees, steps, and timelines.
9. Response Drafter produces a structured draft with citations.
10. Orchestrator transitions to `PENDING_APPROVAL`.
11. Officer reviews: approves, rejects, or edits and approves.
12. Backend verifies approval and authorization before invoking the submission side effect.
13. Usage, cost, trace, and audit records remain queryable.

### 5.2 Officer Actions

| Action           | Effect on `workflow_runs.current_state` | Effect on `approvals`                                                                      |
| ---------------- | --------------------------------------- | ------------------------------------------------------------------------------------------ |
| Approve          | `APPROVED`                              | Insert `decision='approve'`, `approved_content` = draft                                    |
| Reject           | `REJECTED`                              | Insert `decision='reject'`                                                                 |
| Edit and approve | `APPROVED`                              | Insert `decision='edit_and_approve'`, `edited_content` = edits, `approved_content` = final |

**APPROVED** means the response has been approved and is ready for submission. **COMPLETED** means the submission operation succeeded. **REJECTED** is a terminal state.

### 5.3 Submission Gate

The `SubmitOfficialResponseTool` is side-effecting and **cannot be invoked by any LLM agent directly**. Only the Application Layer / Orchestrator may invoke it, and only after an officer has approved.

The submission tool runs inside a single MySQL transaction. Before checking state or performing submission, it acquires a row-level lock on the workflow run via `SELECT ... FOR UPDATE` to prevent concurrent duplicate submissions.

The submission tool is blocked until all of the following hold:

1. An approval decision exists in `approvals` for the run, and its `decision` is `approve` or `edit_and_approve`. The tool uses that record's `approved_content` — never the original draft.
2. The officer is authorized (case owner or admin).
3. `workflow_runs.current_state` is `APPROVED`.

If the run is already `COMPLETED`, the repeated attempt is rejected without creating another submission. It independently verifies case ownership/authorization, the approved decision, and `APPROVED` state at execution time (BR-04).

Within the same transaction, the tool persists `final_response` and transitions the run to `COMPLETED` atomically. Once a run reaches `COMPLETED`, subsequent submission attempts are rejected as a no-op.

The side-effecting submission tool is excluded from automatic retries to prevent duplicate submissions. If the submission destination supports idempotency, that protection is used. Submission in the MVP means recording the approved response as final within the application; it does not imply integration with an external government system unless such integration is explicitly part of the scope.

### 5.4 NEEDS_INFORMATION Resume

When the officer supplies missing information for a run in `NEEDS_INFORMATION`, the same workflow run is resumed rather than creating a new run. The run retains its Run ID, correlation ID, and prior step history.

**Step resumption:** The orchestrator identifies the step to resume by inspecting persisted `workflow_steps` history for the run. Completed steps and their outputs are preserved; successful agents are not rerun unnecessarily. Resumption continues from the step where information was needed.

**Budget reservation while paused:** When a workflow enters `NEEDS_INFORMATION`, its remaining token reservation is released. Previously consumed tokens and historical `llm_calls` records are preserved. When the same run resumes, token estimation and reservation happen again in a MySQL transaction before any further LLM calls are made. No new workflow run is created.

---

## 6. Identifiers and Traceability

| Identifier     | Location                       | Purpose                                                                            |
| -------------- | ------------------------------ | ---------------------------------------------------------------------------------- |
| Run ID         | `workflow_runs.run_uuid`       | Inspectable workflow run identifier (FR-05, FR-09)                                 |
| Correlation ID | `workflow_runs.correlation_id` | End-to-end trace propagated through orchestrator, agents, tools, LLM calls (FR-09) |
| Case ID        | `cases.id`                     | Case ownership and grouping                                                        |
| Chunk ID       | `document_chunks.id`           | Exact citation reference; mirrored into Qdrant point ID                            |

Every agent/workflow start, completion, failure, needs-information, and cancellation event is associated with the Run ID (FR-06).

---

## 7. Streaming and Cancellation

### 7.1 Streaming

Server-Sent Events (SSE) provide live progress events for workflow states and step progress (FR-06). Events are associated with the Run ID.

The completed draft is sent only after schema validation and business-rule checks. Unvalidated partial model output is never exposed through SSE or any other channel.

### 7.2 Cancellation

- An officer can cancel an active workflow.
- Cancellation is tracked via a persisted `cancellation_requested_at` nullable timestamp on `workflow_runs`.
- The worker checks cancellation between steps and after an in-flight provider call returns.
- A late result from a cancelled run must be ignored and must never trigger later steps or submission.
- Backend cancellation prevents subsequent steps and submission.
- The system attempts provider-level cancellation where supported; provider-level cancellation may not be supported for every in-flight request and this limitation is documented honestly.
- Late results from cancelled calls cannot continue the workflow.
- A broken browser connection does not erase run state; status can be retrieved by Run ID.

### 7.3 Cancellation State

Cancelled steps are recorded in `workflow_steps.status = 'cancelled'`. The orchestrator refuses to advance from a cancelled state.

---

## 8. Data Tracking

### 8.1 Workflow Steps (`workflow_steps`)

Each step records: `step_type`, `name`, `input` (JSON), `output` (JSON), `status`, `error_message`, `retry_count`, `started_at`, `completed_at`, `duration_ms`.

Step inputs and outputs must not store passwords, API keys, session secrets, or unnecessary sensitive personal data.

### 8.2 LLM Calls (`llm_calls`)

Each LLM call records: `workflow_run_id`, `user_id`, `agent`, `provider`, `model`, `input_tokens`, `output_tokens`, `input_price_per_1k`, `output_price_per_1k`, `currency`, `model_pricing_id`, `cost`, `reconciled`, `overage_tokens`, `duration_ms`, `status`, `error_message`.

### 8.3 Approvals (`approvals`)

Each approval decision records: `workflow_run_id`, `officer_id`, `decision`, `edited_content`, `approved_content`, `review_comment`, `created_at`.

---

## 9. Clean Architecture Boundaries

| Layer          | Dependencies Allowed                              |
| -------------- | ------------------------------------------------- |
| Domain         | None (pure PHP, no Laravel, no SDKs)              |
| Application    | Domain interfaces only                            |
| Infrastructure | Laravel, LLM SDKs (if used), Qdrant SDK, Eloquent |

Domain and Application layers must not depend on Laravel, Eloquent, provider SDKs, or Qdrant SDK classes. External systems are accessed through ports/interfaces and adapters owned by the application/core (ARCHITECTURE.md §8).

---

## 10. Consistency with Approved Documents

| Requirement                             | Source                  | How Satisfied                                                                                               |
| --------------------------------------- | ----------------------- | ----------------------------------------------------------------------------------------------------------- |
| Three specialized agents + orchestrator | ITI FR-4, BRD FR-04     | Eligibility Identifier, Procedure Resolver, Response Drafter + State Machine Orchestrator                   |
| At least 4 tools                        | ITI FR-4                | SearchRegulationsTool, GetServiceRequirementsTool, CalculateFeesAndTimelineTool, SubmitOfficialResponseTool |
| Side-effecting tool gated by approval   | ITI FR-04, BR-03, BR-04 | SubmitOfficialResponseTool requires approval in `approvals` table                                           |
| State machine with named states         | ITI FR-05, ADR-002      | 11 states with guarded transitions                                                                          |
| Max iterations, timeout, retry/backoff  | ITI FR-05               | Configurable via environment variables                                                                      |
| Runs inspectable by Run ID              | ITI FR-05               | `workflow_runs.run_uuid` unique                                                                             |
| Approve/reject/edit-and-approve         | ITI FR-05               | `approvals.decision` enum                                                                                   |
| Streaming + cancellation                | ITI FR-06               | SSE events, cancellation state in `workflow_steps`                                                          |
| Correlation ID propagation              | ITI FR-09               | `workflow_runs.correlation_id`                                                                              |
| Custom MySQL trace store                | ITI FR-09               | `llm_calls` table                                                                                           |
| Budget-aware routing, hard cut-off      | ITI T3                  | `user_budgets`, `model_pricing`, pre-flight reservation                                                     |
| No citizen-facing portal                | ITI §10, BRD §6         | Citizens do not authenticate; officers enter situations                                                     |
