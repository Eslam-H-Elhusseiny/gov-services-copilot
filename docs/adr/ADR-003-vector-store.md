# ADR-003 — Qdrant as Vector Store

- **Status:** Accepted for MVP; validate operational setup.
- **Date:** 2026-11-21

## Context

The system requires dense retrieval and metadata filtering alongside a relational store.

## Decision

Use Qdrant for vector storage and similarity search. Use MySQL as the source of truth for users, cases, document versions, chunk metadata, traces, budgets, and audit records. Store stable identifiers in Qdrant payloads to resolve results to MySQL records.

## Alternatives Considered

- MySQL-only vectors: not selected because a dedicated vector engine is required for the chosen design.
- Managed vector database: not selected for the portable Docker MVP.
- Other self-hosted vector databases: possible, but Qdrant was selected for a focused integration.

## Consequences

- Compose must persist Qdrant data.
- Collection creation and schema must be repeatable.
- Deletion/re-ingestion must keep MySQL and Qdrant consistent.
- Backups and recovery must be documented.
