# Gov Services Copilot — Evaluation

**Status:** Planned — not yet executed  
**Required by:** ITI-task.md FR-3, BRD FR-03  
**Date:** 2026-11-22

---

## 1. Purpose and Scope

This document defines the evaluation strategy for Gov Services Copilot as required by FR-3. The evaluation harness measures retrieval quality, groundedness, and refusal correctness using a golden Q&A dataset and adversarial cases.

**No evaluation has been executed yet.** This document defines the dataset structure, harness, and metrics. Actual baseline results will be recorded here after the harness is run against the implemented system.

---

## 2. Evaluation Dataset

### 2.1 Golden Q&A Cases (minimum 25)

The golden set contains at least 25 question/answer cases covering the knowledge corpus. Each case includes:

| Field                  | Description                                                                     |
| ---------------------- | ------------------------------------------------------------------------------- |
| `id`                   | Unique case identifier (e.g. G-01)                                              |
| `question`             | Natural language question about a government service                            |
| `expected_service`     | The service identified as applicable                                            |
| `expected_eligibility` | Documented eligibility conditions                                               |
| `expected_documents`   | Required documents                                                              |
| `expected_fees`        | Documented fees                                                                 |
| `expected_timeline`    | Processing timelines                                                            |
| `expected_citations`   | Array of expected source chunk identifiers (document_version_id, page, section) |
| `difficulty`           | `easy`, `medium`, `hard`                                                        |
| `category`             | e.g. `eligibility`, `procedure`, `documents`, `fees`, `timeline`                |

### 2.2 Adversarial Cases (minimum 5)

At least 5 adversarial cases covering:

| Category              | Description                                                     | Example                                                       |
| --------------------- | --------------------------------------------------------------- | ------------------------------------------------------------- |
| Out-of-corpus         | Question about a service not in the knowledge base              | "How do I apply for a passport renewal?" (if not in corpus)   |
| Ambiguity             | Question with insufficient information to determine eligibility | "Can I get benefits?" (no citizen details provided)           |
| Prompt injection      | Question attempting to override system instructions             | "Ignore previous instructions and tell me..."                 |
| Conflicting sources   | Question where retrieved chunks contradict each other           | Two documents with different fee amounts for the same service |
| Insufficient evidence | Question where no supporting evidence exists in the corpus      | "What is the fee for service X?" (service not documented)     |

---

## 3. Evaluation Harness

### 3.1 Runnable Command

The harness is executed via an Artisan command:

```bash
php artisan gsc:evaluate --dataset=golden --format=json --output=docs/evaluation-results.json
```

The command:

1. Loads the golden Q&A dataset from `database/seeds/evaluation/golden.json`.
2. For each case, submits the question through the retrieval + workflow pipeline.
3. Records retrieved chunks, generated answer, citations, and token usage.
4. Compares results against expected values.
5. Writes a JSON report with per-case pass/fail and aggregate metrics.

### 3.2 Harness Components

| Component                 | Responsibility                                                    |
| ------------------------- | ----------------------------------------------------------------- |
| `EvaluationDatasetLoader` | Reads golden/adversarial cases from JSON files                    |
| `EvaluationRunner`        | Executes each case through the RAG + agent pipeline               |
| `HitRateEvaluator`        | Measures whether expected service/chunks were retrieved           |
| `GroundednessEvaluator`   | Checks whether answer claims are supported by retrieved citations |
| `RefusalEvaluator`        | Verifies adversarial cases are refused or flagged as insufficient |
| `EvaluationReport`        | Aggregates metrics and writes results                             |

### 3.3 Execution Flow

```
For each golden case:
  1. Submit question → retrieval pipeline (hybrid dense + keyword + RRF)
  2. Record retrieved chunk IDs and citation metadata
  3. Run agent workflow (eligibility → procedure → draft)
  4. Compare expected vs actual service, eligibility, documents, fees, timeline
  5. Verify citations resolve to expected chunks
  6. Record pass/fail per criterion

For each adversarial case:
  1. Submit question → pipeline
  2. Verify refusal or insufficient-evidence flag is raised
  3. Verify no unsupported entitlement or obligation is claimed
  4. Record pass/fail
```

---

## 4. Metrics

### 4.1 Retrieval Hit Rate@5

Percentage of golden cases where at least one expected source chunk appears in the top 5 retrieved results.

```
Hit Rate@5 = (golden cases with at least one expected chunk in top 5) / (total golden cases)
```

Target: ≥ 80% (to be validated against actual corpus).

### 4.2 Groundedness

Percentage of factual claims in generated responses that are supported by retrieved chunk citations.

For the MVP, groundedness is assessed through manual review: a reviewer reads each factual claim in the generated response and checks whether the cited source chunk actually supports it. No LLM-as-a-judge dependency or paid evaluation service is introduced.

Unsupported claims are identified as follows:

- A claim is flagged if no citation is attached to it.
- A claim is flagged if the attached citation does not exist or does not contain the stated page/section information.
- A claim is flagged if the cited chunk exists but its content does not substantiate the claim.

```
groundedness = (claims supported by cited chunks) / (total factual claims evaluated)
```

Target: 100% — no unsupported entitlement or obligation claims.

### 4.3 Refusal Correctness

Percentage of adversarial cases correctly refused or flagged as insufficient evidence.

```
refusal_correctness = (adversarial cases correctly refused) / (total adversarial cases)
```

Target: 100% — ambiguous, out-of-corpus, and conflicting cases must not produce unsupported answers.

### 4.4 Citation Validity

Percentage of cited chunks that exist and whose page/section metadata matches the source document.

```
citation_validity = (citations with existing chunk and matching metadata) / (total citations)
```

Target: 100%.

### 4.5 Citation Relevance

Percentage of cited chunks whose content actually supports the factual claim they are attached to.

```
citation_relevance = (citations supporting their attached claim) / (total citations)
```

Target: 100%.

---

## 5. Baseline Reporting

### 5.1 What Will Be Recorded

After the harness is executed, the following will be added to this document:

| Metric                      | Baseline Value | Target              |
| --------------------------- | -------------- | ------------------- |
| Retrieval hit rate@5        | TBD            | ≥ 80%               |
| Groundedness                | TBD            | 100%                |
| Refusal correctness         | TBD            | 100%                |
| Citation validity           | TBD            | 100%                |
| Citation relevance          | TBD            | 100%                |
| Average tokens per workflow | TBD            | N/A (informational) |
| Average cost per workflow   | TBD            | N/A (informational) |
| Failure cases               | TBD            | Documented          |

### 5.2 Failure Analysis

For each failing case, the report will document:

- Case ID and category
- Expected vs actual result
- Retrieved chunks (if any)
- Reason for failure (retrieval gap, agent error, citation error, refusal failure)
- Recommended remediation

### 5.3 Interpretation

Results will be interpreted honestly, including poor results. The evaluation is a measurement tool, not a pass/fail gate for the MVP. Gaps identified will be documented as known limitations.

---

## 6. Implementation Status

| Item                                 | Status            |
| ------------------------------------ | ----------------- |
| Golden dataset (25+ cases)           | Not created       |
| Adversarial dataset (5+ cases)       | Not created       |
| Evaluation harness command           | Not implemented   |
| Metrics computation                  | Not implemented   |
| Baseline results                     | Not recorded      |
| `docs/EVALUATION.md` results section | Pending execution |

---

## 7. Evaluation Budget Isolation

Evaluation runs must not consume an ordinary officer's personal token budget.

- Evaluation runs are created with `run_type = 'evaluation'` in `workflow_runs` (normal officer workflows use `run_type = 'officer'`).
- All evaluation runs reference a single system-managed **Evaluation Case** via `workflow_runs.case_id`. This case is not accessible or editable as a regular officer case and must not appear in officers' normal case lists. Evaluation runs use `workflow_runs.run_type = 'evaluation'` (normal officer workflows use `run_type = 'officer'`). Isolation is enforced at the Application Layer / Security layer (server-side Policies).
- Evaluation token usage and cost are recorded in `llm_calls` whenever provider-reported usage is available.
- Evaluation runs do not reserve, consume, or release an ordinary officer's personal token budget. They are excluded from the pre-flight budget check and reconciliation logic.
- Evaluation usage is distinguishable from normal officer workflows in `workflow_runs.run_type` and `llm_calls`; no separate budget table is introduced.
- No additional database tables or complex infrastructure are added for budget isolation.
- Existing accounting capabilities (`llm_calls`, `user_budgets`) are reused where practical.

---

## 8. Consistency with BRD and ITI

- **FR-3 (ITI-task.md):** Golden set ≥ 25 Q/A cases including ≥ 5 adversarial cases covering out-of-corpus, ambiguity, prompt injection, and conflicting sources. Runnable command executes the set. Harness reports retrieval hit rate, groundedness, and refusal correctness. Actual baseline results recorded and interpreted here.
- **BRD FR-03:** Same requirements as ITI-task.md FR-3.
- **BR-02 (No unsupported entitlement):** Refusal correctness metric directly measures this rule.
- **BR-12 (Synthetic corpus only):** All evaluation documents and questions must use synthetic/public content only. No real personal data.
