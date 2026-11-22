# ADR-001 — Chunking and Hybrid Retrieval

- **Status:** Accepted for MVP; validate with corpus experiments.
- **Date:** 2026-11-21

## Context

Government documents contain sections, clauses, tables, and page references. Retrieval must preserve traceability and support both exact terms and semantic questions.

## Decision

Use structure-aware chunking that preserves document/section/page-or-clause/version metadata. Combine Qdrant dense retrieval with MySQL Full-Text keyword retrieval using Reciprocal Rank Fusion (RRF). Use metadata filtering as the one selected retrieval enhancement.

## Alternatives Considered

- Dense-only: weak for exact service names, clause numbers, and official terms.
- Keyword-only: weak for paraphrases and semantic similarity.
- Separate search platform: adds operational complexity to the time-boxed MVP.
- Reranking or HyDE: potentially useful, but not selected as the initial enhancement.

## Consequences

- Both retrieval paths and fusion must be tested and documented.
- RRF parameters and chunking sizes must be tuned against the corpus, not guessed.
- Citations must resolve to exact chunk and version.
- MySQL Full-Text limitations for Arabic/English or tokenization must be measured if bilingual content is later introduced.
