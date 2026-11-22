# Gov Services Copilot

**AI-assisted decision support for government service officers.**

Gov Services Copilot helps government service officers understand citizen situations, identify relevant government services, verify documented eligibility conditions, retrieve procedural requirements, and draft evidence-based official responses.

The system uses Retrieval-Augmented Generation (RAG) and a multi-agent workflow to provide traceable guidance grounded in an approved knowledge corpus. A government officer remains responsible for reviewing and approving every official response.

## What It Does

1. **Citizen Case Submission:** An officer enters the citizen's situation and inquiry.
2. **Evidence Retrieval:** The system retrieves relevant regulations and procedural information with source citations.
3. **Eligibility Identification:** The Eligibility Identifier analyzes documented service and eligibility conditions.
4. **Procedure Resolution:** The Procedure Resolver identifies documented requirements, fees, processing steps, and timelines.
5. **Response Drafting:** The Response Drafter prepares an evidence-based official response.
6. **Human Approval:** The officer reviews the draft and can approve, reject, or edit and approve it.
7. **Run Observability:** Workflow execution history, agent activity, token consumption, and AI costs can be inspected.

**MVP Boundary:** Citizens do not log in to the system. Government officers enter citizen situations on their behalf.

## Architecture and Technology Stack

| Component           | Technology             |
| ------------------- | ---------------------- |
| Backend             | PHP / Laravel 13       |
| Frontend            | Blade / Livewire       |
| Authentication      | Laravel Fortify        |
| Relational Database | MySQL                  |
| Vector Database     | Qdrant                 |
| AI Integration      | Laravel AI SDK         |
| AI Providers        | Groq and Google Gemini |
| Containerization    | Docker Compose         |
| Testing             | PHPUnit / Pest         |

The application follows Clean Architecture principles, separating business logic from framework-specific and external service integrations.

See [System Design](docs/SYSTEM-DESIGN.md) and [Architecture](docs/ARCHITECTURE.md) for further details.

## Core Capabilities

### Document Ingestion

The planned ingestion pipeline supports:

- PDF and DOCX documents.
- Document extraction, cleaning, chunking, embedding, and indexing.
- Document and chunk metadata, including source, section, page, and version.
- Duplicate detection using document hashes.
- Idempotent document processing.
- Processing status and failure reporting.

### Grounded Retrieval

The retrieval pipeline is designed to provide:

- Hybrid retrieval combining semantic vector search and keyword search.
- Reciprocal Rank Fusion (RRF) for combining retrieval results.
- Relevant metadata filtering.
- Source citations traceable to exact document chunks.
- Refusal or clarification when evidence is insufficient, ambiguous, or conflicting.

### Multi-Agent Workflow

The workflow is coordinated by a State Machine Orchestrator and consists of three specialized agents:

- **Eligibility Identifier:** Identifies potentially applicable services and documented eligibility conditions.
- **Procedure Resolver:** Resolves documented requirements, fees, steps, and processing timelines.
- **Response Drafter:** Generates a structured official response supported by retrieved evidence.

### Human Approval

The approval workflow includes:

- Officer review before final submission.
- Approve, reject, and edit-and-approve actions.
- Recorded approval decisions and audit history.
- No consequential submission without explicit authorization.

### Cost Governance and Observability

The MVP focuses on practical cost controls and execution visibility:

- Per-user token budgets.
- Pre-execution token and cost estimation.
- Budget-aware selection between configured AI providers.
- Configurable execution limits and hard budget cut-offs.
- Workflow traces with correlation IDs.
- Token and cost tracking for AI requests, including provider and model information.

## Prerequisites

- Docker Desktop or Docker Engine with Docker Compose support.
- Git.
- API credentials for the configured hosted AI provider.

The application is designed to run through Docker Compose without requiring PHP, Composer, MySQL, or Qdrant to be installed directly on the host machine.

## Getting Started

### 1. Clone the Repository

```bash
git clone <repository-url>
cd gov-services-copilot
```

### 2. Configure Environment Variables

Create your local environment file:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Configure the required application, database, vector database, and AI provider settings in `.env`.

Never commit `.env` or expose API credentials in the repository.

### 3. Start the Application

```bash
docker compose up --build -d
```

### 4. Initialize the Application

Generate the application key and run database migrations:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 5. Prepare the Knowledge Base

Load the provided sample government documents and run the document ingestion process to prepare the searchable knowledge corpus.

### 6. Access the Application

Open the application through its configured local URL and sign in using the available administrator or officer account.

Refer to the setup instructions and environment configuration for any additional initialization steps.

## AI Providers

The system uses a provider abstraction to separate application logic from individual AI providers.

Planned hosted providers:

- **Groq:** Hosted language model inference.
- **Google Gemini:** Alternative hosted AI provider.

Provider selection is designed to be configurable independently of application business logic.

Provider capabilities, model availability, rate limits, streaming, tool calling, embeddings, and usage reporting must be verified against the selected models before being enabled.

API credentials must be supplied through environment variables and must never be committed to source control.

## Testing and Evaluation

The project includes an evaluation plan designed to measure retrieval quality, answer reliability, and refusal behavior.

The evaluation dataset is planned to include:

- At least 25 golden question-and-answer cases.
- At least 5 adversarial cases.

Evaluation metrics include:

- Retrieval hit rate.
- Answer groundedness.
- Citation correctness.
- Refusal correctness.
- Handling of ambiguous and conflicting information.
- Resistance to prompt injection and out-of-corpus questions.

Automated tests are intended to cover domain and application logic, document ingestion, retrieval integration, and agent/tool contracts.

See [Evaluation](docs/EVALUATION.md) for the evaluation methodology and actual results once available.

## Security

Security is considered throughout the application design and implementation.

The planned security controls include:

- Role-based access control.
- Server-side authorization and resource ownership checks.
- Validated document uploads.
- Prompt injection defenses.
- Strict tool permissions and structured tool arguments.
- Human approval for consequential actions.
- API usage limits and token budgets.
- Secure environment configuration and secret management.
- Audit logging without exposing sensitive credentials.

See [Security](docs/SECURITY.md) for security requirements, threat mapping, and implementation status.

## Documentation

| Document                                     | Description                                                               |
| -------------------------------------------- | ------------------------------------------------------------------------- |
| [Business Requirements](docs/BRD.md)         | Business context, stakeholders, objectives, and functional requirements.  |
| [System Design](docs/SYSTEM-DESIGN.md)       | Target architecture, MVP scope, implementation decisions, and trade-offs. |
| [Architecture](docs/ARCHITECTURE.md)         | System components, workflows, data flow, and architectural decisions.     |
| [Security](docs/SECURITY.md)                 | Security requirements, threats, and implementation status.                |
| [Evaluation](docs/EVALUATION.md)             | Evaluation dataset, methodology, metrics, and actual results.             |
| [Agentic Workflow](docs/AGENTIC-WORKFLOW.md) | AI-assisted development workflow and practices.                           |
| [AI Usage Log](docs/AI-USAGE-LOG.md)         | Record of AI assistance, verification, and lessons learned.               |

## Teaching Materials

The project includes a postgraduate teaching package containing:

- A technical teaching presentation.
- A practical lab sheet.
- Lab solutions and answer key.
- Learning outcomes and assessment mapping.
- Common trainee mistakes and recommended corrections.

## Demonstration Videos

The project deliverables include two demonstration recordings:

- **Product Demonstration:** A walkthrough of the implemented system and its main workflow.
- **Teaching Demonstration:** A technical teaching session demonstrating the ability to explain and teach the selected topic.

Recording links will be added when available.

## License and Attribution

See [LICENSE](LICENSE) for licensing information.

Third-party libraries, tools, templates, and external assets are acknowledged where applicable.

The initial application scaffold is generated using the official Laravel application skeleton through Composer and customized for this project.
