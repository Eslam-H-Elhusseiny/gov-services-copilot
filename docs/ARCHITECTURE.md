# Gov Services Copilot — Architecture

## 1. Architectural Style

The MVP uses **Clean Architecture in a modular monolith**. Agents are application components, not independently deployed microservices.

Dependency rule: Presentation and Infrastructure may depend inward; Domain and Application must not depend on Laravel, provider SDKs, Qdrant SDKs, or web-framework types. External dependencies are accessed through ports/interfaces and adapters.

## 2. C4 Level 1 — System Context

```mermaid
flowchart LR
    Officer[Government Officer]
    Admin[System Administrator]
    Copilot[Gov Services Copilot]
    Corpus[(Approved public/synthetic corpus)]
    Providers[Hosted AI Providers: Groq / Gemini]
    Officer -->|case details, review, approval| Copilot
    Admin -->|users, documents, budgets| Copilot
    Copilot -->|retrieve approved knowledge| Corpus
    Copilot <-->|completion, streaming, tools, embeddings| Providers
```

## 3. C4 Level 2 — Container View

```mermaid
flowchart TB
    Officer[Officer Browser]
    Admin[Admin Browser]
    Web[Laravel 13 App\nBlade + Livewire + HTTP API + SSE]
    Worker[Laravel Queue Worker]
    MySQL[(MySQL\nusers, cases, runs, docs, traces, budgets)]
    Qdrant[(Qdrant\nvectors + metadata filters)]
    AI[Groq / Gemini APIs]
    Officer --> Web
    Admin --> Web
    Web --> MySQL
    Web --> Qdrant
    Web --> AI
    Web --> Worker
    Worker --> MySQL
    Worker --> Qdrant
    Worker --> AI
```

## 4. C4 Level 3 — Application Components

```mermaid
flowchart TB
    subgraph Presentation
      UI[Blade / Livewire]
      API[HTTP Controllers]
      SSE[SSE Controller]
    end
    subgraph Application
      UC[Use Cases]
      ORCH[State Machine Orchestrator]
      AG[Eligibility / Procedure / Drafter Agents]
      TOOLS[Tool Services]
      PORTS[Provider, Retrieval, Repository Ports]
    end
    subgraph Domain
      CASE[Case and Run Entities]
      RULES[Eligibility / Approval / Budget Rules]
      CONTRACTS[Typed Contracts]
    end
    subgraph Infrastructure
      DB[MySQL Repositories]
      VEC[Qdrant Adapter]
      PROV[Groq and Gemini Adapters]
      DOC[PDF / DOCX Parsers]
      TRACE[Trace and Usage Persistence]
    end
    UI --> UC
    API --> UC
    SSE --> UC
    UC --> ORCH
    ORCH --> AG
    AG --> TOOLS
    UC --> PORTS
    ORCH --> CASE
    AG --> CONTRACTS
    RULES --> CASE
    PORTS --> DB
    PORTS --> VEC
    PORTS --> PROV
    PORTS --> DOC
    PORTS --> TRACE
```

## 5. Workflow Sequence — Streaming and Approval

```mermaid
sequenceDiagram
    actor Officer
    participant UI as Livewire UI
    participant API as Laravel API
    participant Orch as State Machine
    participant Agents as AI Agents
    participant RAG as Retrieval Tools
    participant LLM as AI Provider
    participant DB as MySQL

    Officer->>UI: Submit citizen case
    UI->>API: Create workflow request
    API->>DB: Save case and workflow run
    API-->>UI: Return workflow Run ID
    UI->>API: Open SSE connection
    API->>Orch: Start workflow

    Orch->>Agents: Run Eligibility Identifier
    Agents->>RAG: Search regulations
    RAG->>DB: Search documents
    RAG-->>Agents: Return relevant chunks and citations
    Agents->>LLM: Analyze eligibility
    LLM-->>Agents: Return structured result
    Agents-->>Orch: Return eligibility result

    Orch->>Agents: Run Procedure Resolver
    Agents->>RAG: Retrieve procedure information
    RAG-->>Agents: Return relevant chunks and citations
    Agents->>LLM: Resolve procedures
    LLM-->>Agents: Return structured result
    Agents-->>Orch: Return procedure result

    Orch->>Agents: Run Response Drafter
    Agents->>LLM: Generate official response draft
    LLM-->>Agents: Return draft and citations
    Agents-->>Orch: Return response draft

    Orch->>DB: Save draft and workflow trace
    Orch-->>UI: Stream progress and draft events

    Officer->>UI: Review response
    Officer->>UI: Submit approval decision
    UI->>API: Send approval action
    API->>DB: Save approval decision

    alt Officer approves
        API->>DB: Verify approval and permissions
        API->>API: Execute submission tool
        API->>DB: Save completion and audit record
        API-->>UI: Workflow completed
    else Officer rejects
        API->>DB: Save rejection
        API-->>UI: Workflow rejected
    end
```

## 6. Data Flow and Trust Boundaries

```mermaid
flowchart LR
    subgraph TrustedApp[Application trust boundary]
      OfficerInput[Officer-entered case]
      Validation[Validation + authorization]
      Orchestrator[Orchestrator and agents]
      Tools[Allowlisted tools]
      Stores[(MySQL + Qdrant)]
      Approval[Human approval check]
    end
    subgraph Untrusted[Untrusted content]
      Docs[Uploaded document content]
      Retrieved[Retrieved chunks]
      ModelOutput[LLM output]
    end
    subgraph External[External provider boundary]
      Provider[Hosted AI provider]
    end
    Docs -->|parse, validate, mark untrusted| Stores
    OfficerInput --> Validation --> Orchestrator
    Stores --> Retrieved --> Orchestrator
    Orchestrator -->|prompt + minimum necessary context| Provider
    Provider --> ModelOutput --> Validation
    Validation --> Tools
    Tools --> Stores
    Tools --> Approval
    Approval -->|approved only| Submission[Side-effecting submission]
```

Trust rules

- Uploaded and retrieved document text is data, never privileged instruction.
- LLM output is untrusted until schema-validated and business-rule checked.
- Tool arguments are validated against typed schemas.
- No model output is passed directly to SQL, shell, raw HTML, or filesystem paths.
- Per-agent tool allowlists restrict capabilities.
- The provider receives only the context needed for the task; exact data leaving the application must be documented after implementation.
- No real personal data is used in the corpus or demos.

## 7. ER Diagram — Initial Logical Model

```mermaid
erDiagram
    USERS ||--o{ CASES : creates
    CASES ||--o{ WORKFLOW_RUNS : has
    WORKFLOW_RUNS ||--o{ AGENT_EXECUTIONS : contains
    WORKFLOW_RUNS ||--o{ TOOL_INVOCATIONS : contains
    WORKFLOW_RUNS ||--o{ LLM_TRACES : contains
    WORKFLOW_RUNS ||--o{ APPROVALS : receives
    DOCUMENTS ||--o{ DOCUMENT_VERSIONS : has
    DOCUMENT_VERSIONS ||--o{ CHUNKS : contains
    USERS ||--o{ TOKEN_USAGE : incurs
    WORKFLOW_RUNS ||--o{ TOKEN_USAGE : accounts
    USERS ||--o{ AUDIT_EVENTS : performs
    USERS ||--o{ USER_BUDGETS : assigned
    USERS {
      bigint id PK
      string role
      string email
    }
    CASES {
      bigint id PK
      bigint owner_id FK
      text situation
    }
    WORKFLOW_RUNS {
      bigint id PK
      bigint case_id FK
      string status
      string correlation_id
    }
    DOCUMENTS {
      bigint id PK
      string source_name
    }
    DOCUMENT_VERSIONS {
      bigint id PK
      bigint document_id FK
      string content_hash
      string version_label
    }
    CHUNKS {
      bigint id PK
      bigint document_version_id FK
      string source_location
      text content
    }
```

This is a logical starting point, not a finalized migration schema. Add fields and constraints only as implementation decisions are made.

## 8. Layer Dependency Rule

```mermaid
flowchart TB
    Presentation[Presentation: Laravel HTTP / Blade / Livewire]
    Infrastructure[Infrastructure: MySQL / Qdrant / AI adapters / parsers]
    Application[Application: use cases / ports / orchestration]
    Domain[Domain: entities / value objects / business rules]
    Presentation --> Application
    Infrastructure --> Application
    Application --> Domain
    Infrastructure --> Domain
```

Domain and Application must not import Laravel, Eloquent, provider SDK, or Qdrant SDK classes.

## 9. ADR Index

- [ADR-001 — Chunking and Hybrid Retrieval](adr/ADR-001-chunking-and-retrieval.md)
- [ADR-002 — State Machine Orchestration](adr/ADR-002-state-machine-orchestration.md)
- [ADR-003 — Qdrant Vector Store](adr/ADR-003-vector-store.md)
- [ADR-004 — Cost Governor](adr/ADR-004-cost-governor.md)

All diagrams are committed as Mermaid source in this file.
