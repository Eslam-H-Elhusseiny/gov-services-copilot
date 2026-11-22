# ADR-002 — State Machine Orchestration

- **Status:** Accepted.
- **Date:** 2026-11-21

## Context

The government workflow has a known sequence: eligibility, procedure resolution, response drafting, and officer approval. It must pause for missing information and stop on unsafe evidence.

## Decision

Use a deterministic State Machine Orchestrator rather than an autonomous supervisor/planner. The orchestrator controls named states and guarded transitions. Specialized agents perform bounded tasks and return typed contracts.

## Alternatives Considered

- Supervisor agent: flexible, but adds nondeterministic routing and more difficult debugging.
- Planner-executor: useful for open-ended tasks, but unnecessary for this fixed workflow.
- Simple linear pipeline: insufficiently expressive for needs-information, failure, cancellation, and approval states.

## Consequences

- State transitions are explicit and testable.
- Retry, timeout, iteration limits, cancellation, and approval are enforced centrally.
- Less flexible than an autonomous supervisor, but easier to inspect and teach.
