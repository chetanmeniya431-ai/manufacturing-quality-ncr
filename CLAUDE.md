# Manufacturing Quality & NCR Management System — CLAUDE.md

## What this product does

A quality management system for ISO-certified factories. When a product or
process does not meet the standard, a Non-Conformance Report (NCR) is raised.
This system manages the full NCR lifecycle: logging → investigation → root cause
analysis → corrective action → closure. AI (Ollama, no external API key) reads
the factory's quality manual and past NCRs to suggest root causes and verify
that corrective actions match what the standard requires. Signals fire
automatically when NCRs are overdue, suppliers cause repeat failures, or the
same defect appears too often. One-click audit reports give ISO auditors a
complete, organised evidence pack.

Sells to: ISO 9001 certified manufacturers, quality managers, production
directors at factories with 10–500 staff.

---

## Tech stack (fixed — do not change)

- **Backend:** Laravel 13 (PHP 8.3)
- **Database:** PostgreSQL 16 with the `pgvector` extension (`pgvector/pgvector:pg16`
  image) — vector similarity search (document chunks, NCR embeddings) runs as
  native `vector(768)` columns with HNSW indexes and the `<=>` cosine-distance
  operator, via the `pgvector/pgvector` PHP package (`Pgvector\Laravel\Vector`
  cast + `HasNeighbors`/`nearestNeighbors()`). No PHP-side cosine-similarity
  loops over JSON arrays.
- **Frontend:** Laravel Blade + Livewire 4 + Tailwind CSS v4 + Alpine.js + Vite
- **Runtime:** Docker (docker-compose) — no local PHP or Postgres needed
- **Icons:** Heroicons inline SVG only
- **Font:** Inter (Google Fonts)
- **LLM generation:** Ollama — `llama3.2:3b` (root cause suggestion, corrective action review, quality assistant answers)
- **Embeddings:** Ollama — `nomic-embed-text`, 768 dims (similar NCR detection, document RAG)
- **PDF export:** `barryvdh/laravel-dompdf`
- **Excel import:** `maatwebsite/excel`
- **PDF parsing:** `smalot/pdfparser` (for quality document upload)

No external API keys. All AI runs locally via Ollama, reached from inside the
app container at `http://host.docker.internal:11434` — a single shared Ollama
instance already running on the host (port 11434) with `llama3.2:3b` and
`nomic-embed-text` pulled, shared across all projects on this machine. There is
no `ollama` service in this project's `docker-compose.yml`.

---

## Assigned ports

- **APP_PORT:** 8004
- **DB_EXTERNAL_PORT:** 33064 (Postgres, mapped from container port 5432)
- **OLLAMA_PORT:** 11434 (shared host Ollama instance, not a container this project starts)

---

## Shared infrastructure rules

**Ollama — use the host, never add a container:**
Do NOT add an `ollama` service to this project's `docker-compose.yml`.
Ollama runs once on the host machine and is shared by all Nirmantra projects.
The app reaches it at `http://host.docker.internal:11434` — Docker maps this
address to the host from inside any container automatically.

Models are pulled once on the host and reused by every project:
```bash
ollama pull llama3.2:3b
ollama pull nomic-embed-text
```

If models are already pulled (check with `ollama list`), skip the pull step.
Loading the same model twice — once per project container — wastes 2 GB of RAM
per duplicate. One host instance serves all projects simultaneously.

**Database — fully isolated, do not share:**
This project's database runs in its own container with its own Docker volume.
No other project connects to it. Never point this project at another project's
database port. Never share volumes between projects.

---

## Docker setup

The app container has no `.env` file of its own — it's configured entirely via
the real environment variables `docker-compose.yml` passes in (see
`docker/entrypoint.sh`). That means `APP_KEY` must be generated into the
**host** `.env` before `docker compose up`, not with `key:generate` run inside
the container afterwards (that would write to a file that doesn't persist).

```bash
cp .env.example .env

# Generate an APP_KEY and paste it into .env (APP_KEY=base64:...):
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));"

docker compose up -d --build
docker compose exec app php artisan db:seed --force
```

Migrations run automatically on container boot (`docker/entrypoint.sh`), so
`db:seed` is the only manual step after `up`. If `APP_KEY` is left blank, the
entrypoint generates a temporary one for that process tree only — sessions
and encrypted values won't survive a container restart, so set a real one for
anything beyond a quick local check.

App at `http://localhost:8004`
Default login: `admin@qualitymanager.local` / `password`

---

## Roles

Five roles using Spatie Laravel Permission:

- **Quality Manager** — full access. Closes NCRs, manages signals, exports audit reports.
- **Quality Inspector** — creates and investigates NCRs. Cannot close or delete.
- **Production Manager** — views NCRs affecting their line. Assigns corrective actions.
- **Supplier** — external login. Sees only NCRs linked to their supplier account. Can submit responses.
- **Auditor** — read-only access to everything. Can export reports. Cannot create or edit.

---

## MVP Features (5 only — build these, nothing else)

### 1. NCR logging and workflow

Create an NCR with:
- NCR number (auto-generated: `NCR-YYYY-NNNN`)
- Product or process name
- Defect category: Dimension out of spec / Surface defect / Material non-conformance / Process deviation / Supplier defect / Documentation error
- Description (free text)
- Severity: Minor / Major / Critical
- Where detected: Incoming inspection / In-process / Final inspection / Customer return
- Detected by (user)
- Detected date
- Related supplier (if supplier defect — links to supplier record)
- Attachments (photos, measurement records)

Status workflow (linear, can only move forward):
```
Open → Under Investigation → Corrective Action Assigned → Verification → Closed
```

Each status change records who made it and when. Closed NCRs require a closure
note and confirmation that the corrective action was verified effective.

Bulk import from CSV (download sample template from the UI).

### 2. Quality document management with RAG

Upload quality documents as PDF files:
- Quality manual
- Product specifications
- Work instructions
- Supplier quality requirements
- ISO standard summaries

Each document is parsed with `smalot/pdfparser`, split into 500-token chunks,
embedded with `nomic-embed-text` via Ollama, and stored in the `document_chunks`
table with the embedding as a native pgvector `vector(768)` column (HNSW index,
cosine distance).

Upload works two ways, both going through the exact same
`ProcessQualityDocumentJob` pipeline (extract → chunk → embed → store), so
there is no seed-only shortcut:
- **Manually, from the UI** — "Quality Documents" → Upload. The PDF is stored
  on disk, a `quality_documents` row is created with `status: pending`, and
  the job is queued immediately.
- **From the seeder** — the demo PDFs are generated as real files under
  `storage/app/quality-documents/`, then dispatched through the same job
  (synchronously during seeding), so the seeded documents prove the manual
  path works, not a hand-inserted fixture.

Staff can ask questions in the "Quality Assistant" tab:
- "Does our quality manual require a CAPA for a major defect?"
- "What is the acceptance criterion for surface roughness on product X?"
- "How long must we retain NCR records under ISO 9001?"

The system retrieves the top 5 chunks by cosine similarity, passes them to
`llama3.2:3b` with the question, and returns the answer with a source reference
(document name, page estimate).

### 3. AI root cause suggestion using embeddings

When a new NCR is saved, the system embeds its description using `nomic-embed-text`
and runs a pgvector cosine-distance nearest-neighbor query (`<=>` operator,
HNSW index) against the `ncr_embeddings` table for all past closed NCRs.

It surfaces the top 3 most similar past NCRs — showing: their description, their
root cause, and the corrective action that resolved them.

Below the similar cases, an AI-generated suggestion is shown:
> "Based on 3 similar past NCRs, the likely root cause is [X]. The corrective
> action that worked before was [Y]."

This is generated by passing the similar cases and the new NCR description to
`llama3.2:3b`. The suggestion is shown as a starting point — the investigator
fills in the actual root cause themselves.

The AI suggestion is logged in the NCR record but does not set any field
automatically. The user must confirm or edit before saving.

### 4. Supplier NCR tracking and quality score

Each NCR can be tagged to a supplier (from the supplier register).

Supplier register stores: supplier name, contact, product categories supplied,
approved status.

Supplier quality score is calculated automatically:
- Start at 100
- Deduct 5 points per Minor NCR in the last 90 days
- Deduct 15 points per Major NCR in the last 90 days
- Deduct 30 points per Critical NCR in the last 90 days
- Score recovers by 1 point per week with no NCRs

Supplier list view shows each supplier's current score with a colour indicator:
- Green: 80–100
- Yellow: 60–79
- Red: below 60

When a supplier's score drops below 60, a signal fires automatically (see
Signals Engine).

### 5. Signals engine and audit report export

**10 default signals (seeded, active by default):**

| # | Name | Condition | Severity |
|---|------|-----------|----------|
| 1 | NCR Investigation Overdue | Open NCR not moved to investigation within 3 days | High |
| 2 | Corrective Action Overdue | NCR in "Corrective Action Assigned" status for 14+ days | High |
| 3 | Repeat Defect — Same Category | Same defect category appears 5+ times in 30 days | High |
| 4 | Repeat Defect — Same Product | Same product has 3+ NCRs in 30 days | Medium |
| 5 | Supplier Quality Score Critical | Supplier score drops below 60 | High |
| 6 | Critical NCR — No Immediate Action | Critical severity NCR open for 24+ hours without status change | Critical |
| 7 | Supplier Repeat Failure | Same supplier causes 3+ NCRs in 60 days | High |
| 8 | NCR Verification Overdue | NCR in "Verification" status for 7+ days | Medium |
| 9 | Customer Return NCR | Any NCR with "Customer return" as detection point | High |
| 10 | Monthly NCR Spike | This month's NCR count exceeds last month's by 50%+ | Medium |

Signals run via the Laravel scheduler every hour. Each fired signal creates a
`signal_events` record. The signals dashboard shows all open events with the
NCR(s) that triggered them.

**Audit Report (PDF export):**

One button on the NCR list: "Export Audit Report". Generates a PDF with:
- Summary: total NCRs by status, by severity, by category (this period)
- NCR list with all fields
- Corrective actions with completion status
- Supplier quality scores
- All open signal events
- Document list (what quality documents are uploaded)

Period is selectable: last 30 / 90 / 180 days, or custom range.

---

## Database schema

```
suppliers
  id, name, contact_name, contact_email, product_categories (json),
  approved (boolean), created_at, updated_at

ncrs
  id, ncr_number (unique), product_name, defect_category (enum),
  description (text), severity (enum: minor|major|critical),
  detected_at (enum: incoming|in_process|final|customer_return),
  detected_by (FK users), detected_date,
  supplier_id (FK nullable), attachments (json),
  status (enum: open|investigating|corrective_assigned|verification|closed),
  root_cause (text nullable), corrective_action (text nullable),
  corrective_action_assigned_to (FK users nullable), corrective_action_due (date nullable),
  verification_notes (text nullable), closure_notes (text nullable),
  closed_by (FK users nullable), closed_at (timestamp nullable),
  ai_similar_ncrs (json nullable — top 3 similar NCR IDs and scores),
  ai_root_cause_suggestion (text nullable),
  investigation_started_at, corrective_action_assigned_at, verification_started_at
    (timestamps, nullable — set when the NCR enters each stage; the signals
    engine uses these, not created_at/updated_at, to check "N days in this
    stage" without being thrown off by unrelated edits),
  created_by (FK users), created_at, updated_at

ncr_status_history
  id, ncr_id (FK), old_status, new_status, changed_by (FK users),
  note (text nullable), created_at

quality_documents
  id, name, file_path, document_type (enum: quality_manual|product_spec|work_instruction|supplier_req|standard),
  status (enum: pending|processing|ready|failed), error_message (text nullable),
  chunk_count (int), embedded_at (timestamp nullable),
  created_by (FK users), created_at, updated_at

document_chunks
  id, document_id (FK), chunk_index, chunk_text (text), page_estimate (int nullable),
  embedding (vector(768) — pgvector column, nomic-embed-text dims, HNSW cosine index),
  created_at

ncr_embeddings
  id, ncr_id (FK unique), embedding (vector(768) — pgvector column, HNSW cosine index),
  created_at, updated_at

signals
  id, name, description, condition_key (string), threshold (int),
  window_days (int), severity (enum: low|medium|high|critical),
  active (boolean), created_at, updated_at

signal_events
  id, signal_id (FK), ncr_id (FK nullable), supplier_id (FK nullable),
  triggered_at, resolved_at (nullable), resolved_by (FK users nullable),
  note (text nullable),
  context (json nullable — dedup/group key for signals not tied to one NCR or
    supplier row, e.g. repeat-defect-by-category or the monthly spike check;
    holds things like {"category": "dimension_out_of_spec"} or {"month": "2026-09"}),
  created_at
```

---

## Scheduled jobs

```php
$schedule->command('signals:check')->hourly();
$schedule->command('embeddings:process-pending')->everyFifteenMinutes();
```

`signals:check` — loops through all active signals, evaluates each condition,
fires new events if threshold crossed, auto-resolves events where condition
no longer applies.

`embeddings:process-pending` — finds NCRs and document chunks without
embeddings and sends them to Ollama `nomic-embed-text` in batches of 10.

---

## Environment variables

```env
APP_NAME="Quality NCR Manager"
APP_ENV=local
APP_PORT=8004

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=quality_ncr
DB_USERNAME=quality_ncr
DB_PASSWORD=secret
DB_EXTERNAL_PORT=33064

OLLAMA_BASE_URL=http://host.docker.internal:11434
OLLAMA_GENERATION_MODEL=llama3.2:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
EMBEDDING_DIMENSIONS=768
OLLAMA_TIMEOUT=300

CHUNK_SIZE=500
CHUNK_OVERLAP=50
SIMILARITY_THRESHOLD=0.5
TOP_K_CHUNKS=5

SIGNAL_CHECK_INTERVAL_HOURS=1
EMBEDDING_BATCH_SIZE=10

QUEUE_CONNECTION=database
```

The app container runs php-fpm + nginx + a `queue:work` worker + the Laravel
scheduler (via supervisord — see `docker/supervisord.conf`), so uploads,
embeddings, and the hourly `signals:check` job all run automatically once
`docker compose up -d` is running — no separate cron or manual dispatch needed.

---

## Synthetic data plan (seeder)

Creates a demo manufacturing company: **Vantage Precision Parts Ltd** (auto
parts manufacturer, ISO 9001 certified).

**Users (5):**
- `admin@qualitymanager.local` / `password` — Quality Manager
- `inspector@qualitymanager.local` / `password` — Quality Inspector
- `production@qualitymanager.local` / `password` — Production Manager
- `supplier@qualitymanager.local` / `password` — Supplier (linked to one supplier)
- `auditor@qualitymanager.local` / `password` — Auditor

**Suppliers (5):** the quality score is always computed live from NCR history
(see `Supplier::qualityScore()`), never stored. Seeded NCRs are shaped so
that at seed time:
- Steelcore Components Ltd — good (no NCRs in the last 90 days)
- Apex Fasteners Pvt Ltd — critical, below 60 (3 major/critical NCRs within
  the last 30 days — deliberately triggers the supplier signal)
- Prima Rubber Seals — acceptable (one old minor NCR)
- Zenith Coatings — excellent (no NCRs at all)
- Metalform Industries — watch (one recent minor/major NCR)

**Quality documents (3 uploaded, with realistic content):**
- ISO 9001:2015 Quality Manual (excerpt — 12 pages synthetic text)
- Product Specification — Precision Shaft Type A (8 pages)
- Supplier Quality Requirements — rev 4 (6 pages)

**NCRs (30 total):**
- 12 closed (with root cause, corrective action, closure notes)
- 8 in corrective action assigned stage
- 5 under investigation
- 3 open (recently logged)
- 2 in verification

Defect category distribution: dimension out of spec (8), surface defect (7),
supplier defect (6), process deviation (5), material non-conformance (3),
documentation error (1).

Severity distribution: Minor (15), Major (12), Critical (3).

3 NCRs are linked to Apex Fasteners (triggering supplier signal).
5 NCRs share the "dimension out of spec" category in the last 30 days
(triggering repeat defect signal).

**Signals:** All 10 seeded. 3 signal events pre-triggered:
- NCR Investigation Overdue (2 events)
- Supplier Quality Score Critical (Apex Fasteners)
- Repeat Defect — Same Category (dimension out of spec)

All data is fully synthetic. No real company names or actual defect records.

---

## Design

Inherits the Nirmantra design system.

- **Accent colour:** Amber (`amber-600`) — manufacturing, attention, quality
- **Layout:**
  - Sidebar + main content area
  - Sidebar links: Dashboard, NCRs, Suppliers, Quality Documents, AI Assistant,
    Signals, Reports, Settings (admin only)
- **NCR list:** table view with severity badge (colour-coded), status badge,
  NCR number, product name, detected date, assigned to
- **Severity badges:**
  - Critical: `red`
  - Major: `orange`
  - Minor: `yellow`
- **Signal events:** shown as alert cards on the dashboard, colour-coded by severity
- **Mobile:** tables scroll horizontally. NCR create form is usable on phone.

---

## Quality checklist

- [ ] Cold start: `docker compose up` + seed works with zero manual steps
- [ ] NCR workflow moves through all 5 stages correctly
- [ ] CSV bulk import creates NCRs and validates required fields
- [ ] Quality document upload triggers chunking and embedding automatically
- [ ] AI Assistant answers questions correctly from the uploaded quality manual
- [ ] Similar NCR suggestion appears on new NCR creation (after seed is embedded)
- [ ] All 10 signals evaluate correctly — verify the 3 pre-triggered ones fire
- [ ] Supplier quality score calculates correctly based on linked NCRs
- [ ] Audit report PDF generates with correct data for the selected period
- [ ] Supplier role sees only their own NCRs — no other supplier's data visible
- [ ] Auditor role cannot create or edit anything
- [ ] No Ollama API key or external API key anywhere in the codebase
- [ ] `.env.example` documents every variable
- [ ] Works on mobile screen (375px minimum width)
