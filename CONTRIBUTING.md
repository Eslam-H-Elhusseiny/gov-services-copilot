# Contributing

## Workflow

1. Start from a GitHub Issue with clear acceptance criteria.
2. Create a focused feature branch from `main`.
3. Make one coherent change per commit.
4. Run relevant tests, lint, and security checks before opening a PR.
5. Open a Pull Request; never push directly to `main`.
6. Describe what changed, why it changed, how it was tested, and any known limitations.
7. Self-review the diff, add inline review comments where useful, and link the related issue (e.g., `Closes #12`).
8. Merge only after required CI checks pass.

## Commit Convention

Use Conventional Commits:

- `feat:` — new functionality
- `fix:` — bug fixes
- `docs:` — documentation changes
- `test:` — tests
- `refactor:` — code restructuring
- `build:` — build system and dependencies
- `ci:` — CI/CD configuration

Commit messages should explain intent. Avoid `WIP`, `fix2`, or `final-final`.

Keep commits focused and meaningful.

## Quality and Security

- Keep Domain and Application layers independent of frameworks and provider SDKs.
- Add tests for behavior, integration points, and security boundaries.
- Never commit `.env`, API keys, real personal data, or private documents.
- Keep dependency lockfiles committed and review dependency changes.
- Do not claim unimplemented behavior in documentation.
- Document significant design decisions and intentional scope limitations.

## Pull Request Expectations

Every Pull Request should include:

- **What:** Summary of the changes.
- **Why:** The reason for the changes.
- **How tested:** Commands or checks performed and their results.
- **Limitations:** Known gaps or deferred work.
- **Related issue:** A linked GitHub Issue.

Review your own changes before requesting a merge. All changes must be merged through Pull Requests.
