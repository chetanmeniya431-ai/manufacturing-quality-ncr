# Quality NCR Manager

A non-conformance report (NCR) tracking system for manufacturing businesses. Staff log quality failures, the AI assistant finds similar past incidents before you re-investigate, and every NCR moves through a structured workflow from detection to sign-off.

Runs entirely on your own server. No third-party AI costs — uses Ollama for local language models.

---

## Requirements

- Docker and Docker Compose
- [Ollama](https://ollama.com) running on the host machine with these models pulled:
  ```bash
  ollama pull llama3.2:3b
  ollama pull nomic-embed-text
  ```

No local PHP, Node, or PostgreSQL install needed.

---

## Quick start

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open **http://localhost:8004**

---

## Demo logins

All demo accounts use password `password`.

| Email | Role |
|---|---|
| admin@qualitymanager.local | Quality Manager |
| inspector@qualitymanager.local | Quality Inspector |
| production@qualitymanager.local | Production Manager |
| supplier@qualitymanager.local | Supplier |
| auditor@qualitymanager.local | Auditor |

---

## Features

- Log non-conformance reports in under 2 minutes
- AI finds similar past failures before you re-investigate
- Full workflow: detection → root cause → corrective action → sign-off
- Supplier-linked NCRs for incoming material failures
- Audit-ready PDF report of every open and closed NCR
- Role-based access — inspectors, production staff, suppliers, and auditors each see only what they need

---

## Key configuration

All settings are in `.env`. Copy `.env.example` to `.env` and adjust:

| Variable | Description |
|---|---|
| `APP_URL` | Public URL of the app |
| `APP_PORT` | Host port (default `8004`) |
| `OLLAMA_BASE_URL` | Ollama server URL (default `http://host.docker.internal:11434`) |
| `OLLAMA_GENERATION_MODEL` | LLM for AI responses (default `llama3.2:3b`) |
| `OLLAMA_EMBEDDING_MODEL` | Embedding model for semantic search (default `nomic-embed-text`) |
| `MAIL_MAILER` | Set to `smtp` with SMTP credentials for real email delivery |

For production: set `APP_ENV=production`, `APP_DEBUG=false`, and use a strong `DB_PASSWORD`.

---

## Tech stack

Laravel 11 · PHP 8.3 · PostgreSQL + pgvector · Livewire v3 · Tailwind CSS · Ollama · Docker
