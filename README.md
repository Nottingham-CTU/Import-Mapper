# Import Mapper REDCap External Module

Advanced CSV importer for REDCap.

## Overview

Import Mapper is a project-scoped REDCap external module that allows administrators and designated importers to define reusable field mappings and import CSV data into REDCap projects. It supports classic and longitudinal projects, repeating events and forms, record/event/form instance matching, field value transformations, and chunked background processing.

## Requirements

- REDCap with External Module Framework v16+
- PHP 8.4+
- Node.js (for frontend development only)

## Installation

1. Download or clone this repository into your REDCap `modules/` directory as `import_mapper_v0.0.0/`.
2. In REDCap, go to **Control Center > External Modules** and enable Import Mapper.
3. Navigate to a project and enable the module under **Project Settings > External Modules**.

## Configuration

Module settings are configured per project under **External Modules > Import Mapper > Configure**.

| Setting | Type | Description |
|---|---|---|
| Admin Roles | user-role-list (repeatable) | REDCap role names with full admin access to the module |
| Importer Roles | user-role-list (repeatable) | REDCap role names with import-only access |
| Notification Emails | text (repeatable) | Email addresses to notify on import completion |

> REDCap super-users always have full admin access regardless of role configuration.

## User Roles

| Role | Permissions |
|---|---|
| Admin | Full access: create, edit, copy, delete mappings; import; view logs |
| Importer | Import using existing mappings; view mappings (read-only); view logs |
| Other | No access |

## Features

- **Mapping wizard** — define a mapping name, extract column names from a sample CSV upload, configure field mappings, and set instance matching options
- **Project hash validation** — mappings are linked to a snapshot of the project structure; if the project changes, the mapping is placed in draft status and must be reviewed before importing
- **Chunked background import** — CSV files are processed in 500-row chunks per cron run, allowing large imports without hitting request timeouts
- **Record matching** — optionally match incoming rows to existing records by a chosen field value instead of always creating new records (field does not have to be the record id field)
- **Event matching** — optionally match incoming rows to existing instances of a repeating event
- **Form matching** — optionally match incoming rows to existing instances of a repeating form (classic or longitudinal)
- **DAG resolution** — Data Access Group assignment can either be the same for every row or based on the value of a particular column
- **Field value transformations** — apply regex substitution, date format conversion, or field combining rules to field values before saving
- **Import logs** — per-import log entries with a summary of any errors or warnings
- **Active job monitoring** — live progress display with cancel support while an import is running
- **Email notifications** — configurable addresses are notified when an import completes or fails, and any errors/warnings are listed with their associated row number

## Using Import Mapper

### Creating a Mapping

Mappings are created through a 4-step wizard.

#### Step 1 — Name

- Enter a unique name for the mapping.

#### Step 2 — Sample CSV

- Upload a CSV file. Import Mapper reads the header row to discover available column names; data rows are ignored.
- If column names already exist from a previous upload, choose to append (merge) or overwrite them.
- All columns must have non-empty header names.

#### Step 3 — Field Mappings

- A table where each row maps one CSV column to one REDCap field.
- **Classic projects:** CSV column → Form → Field.
- **Longitudinal projects:** CSV column → Event → Form → Field. Repeating forms and events are indicated with a '🔁' symbol.
- At least one row is required. Add more rows with "Add field mapping".
- Each row has an optional **Transforms** button — see [Transformations](#transformations) below.

#### Step 4 — Matching & DAG Configuration

Four independently enabled sections.

**Record Matching**
- Off by default: every CSV row creates a new REDCap record.
- When enabled: choose a field mapping as the matching key. If an existing record has the same value in that field, it is updated instead of a new record being created. The matching field does not need to be the record ID field.

**Event Matching** *(longitudinal projects with repeating events only)*
- One row per repeating event. Enable matching per event.
- Choose the Form and Field within the event to use as the matching key for instances.
- If a match is found in an existing instance, that instance is updated; otherwise a new instance is created.

**Form Matching** *(projects with repeating forms only)*
- One row per repeating form. Enable matching per form.
- Choose the Field within the form to use as the matching key for instances.
- Same match/create logic as event matching.

**DAG Assignment**
- Off by default.
- When enabled, choose a mode:
  - *Same DAG for all records*: select a fixed DAG from the project's defined DAGs.
  - *CSV column contains DAG*: select the CSV column whose values are DAG unique names. Rows with unrecognised DAG names will fail during import.

#### Saving

- **Save Draft** — available at any step; saves progress but keeps the mapping in draft status (cannot be used for imports).
- **Save** — on the final step, saves the mapping as final and ready to import.

---

### Transformations

Configured per field mapping via the **Transforms** button in Step 3. Applied in this order at import time:

1. **Field Combining** — combine the primary CSV column with additional CSV columns using a template string. Placeholders `{1}`, `{2}`, `{3}` etc. refer to the primary field and additional fields in order (e.g. `{1} {2}` concatenates two fields with a space). Each additional field can have its own regex applied before combination.

2. **Regex** — apply a PHP `preg_replace`-style find/replace to the (possibly combined) value. Enter the pattern (e.g. `/(\d{3})(\d{3})(\d{4})/`) and the replacement (e.g. `$1-$2-$3`). A live preview is shown in the transform editor.

3. **Date Conversion** — convert a date from a common input format (MM/DD/YYYY, DD/MM/YYYY, or YYYY/MM/DD) to the REDCap-required YYYY-MM-DD format.

4. **Value Mapping** — define a lookup table of input → output substitutions. Values not in the table pass through unchanged.

---

### Project Hash & Draft Status

Every saved mapping is linked to a snapshot of the project structure at the time it was saved. If the REDCap project changes (fields, forms, or events added, removed, or renamed; repeating configuration changed), the mapping is automatically placed in **draft status**.

- Draft mappings cannot be used for imports.
- Open the mapping and work through the wizard. Fix or remove any rows that reference fields that no longer exist/have been renamed, then click **Save** to restore the mapping to final status.

---

### Running an Import

1. From the dashboard, click **Import** on a final-status mapping.
2. Upload a CSV file. Import Mapper validates that the file's column names match the mapping and shows a preview of the first 5 rows.
3. Click **Start Import**. The file is queued and you can leave the page. The background cron processes the file in 500-row chunks (runs every 60 seconds).
4. Monitor progress from the **Imports** page. Active jobs show a progress bar and a **Cancel** button.

**Import outcomes:**

| Outcome | Meaning |
|---|---|
| Completed — Clean | All rows imported with no issues |
| Completed — Warnings | All rows processed; some non-critical warnings were raised |
| Completed — Errors | All rows processed; some rows had errors and were skipped |
| Failed | Import stopped early due to a critical error |
| Cancelled | User cancelled; rows already saved are not automatically undone |

---

### Import Logs

The Imports page lists all past imports with: status, outcome, mapping name, username, timestamp, row counts, and error counts broken down by category:

- **Mapping errors** — configuration problems detected at import time
- **CSV structure errors** — problems with the file's structure (e.g. missing columns)
- **CSV data errors** — invalid values in individual rows
- **Transformation errors** — errors raised while applying transforms
- **REDCap save errors** — errors returned by REDCap when saving data

Each log entry links to a detail view.

---

## Frontend Development

```bash
npm install
npm run build        # Production build
npm run build:dev    # Watch mode build
```

**Stack:** React 19, TanStack Router (file-based routing in `src/routes/`), Vite.

## Architecture Overview

```
ImportMapper.php            Entry point, extends AbstractExternalModule
  └─ redcap_module_ajax()   Dispatches AJAX actions to controllers

classes/Controllers/        Handle AJAX requests, validate input, return JSON
classes/Services/           Business logic (ImportService, RowProcessor, ProjectService, …)
classes/Repositories/       Data access (MappingRepository, RecordRepository, …)
classes/Models/             Value objects and data models

pages/upload.php            Multipart file upload endpoint (queues import job)
pages/dashboard.php         Module entry page
```

**Import flow:**

1. User uploads a CSV via `upload.php` — the file is saved to `/tmp/import_mapper/` and a job is queued in a REDCap project setting.
2. The `process_import_jobs` cron (every 60 s) picks up queued jobs and processes one 500-row chunk per run.
3. On the final chunk a log entry is written with the outcome (`completed`, `failed`, or `cancelled`).
4. The frontend polls `get_active_jobs` (run state) and `get_logs` (terminal entries) to display live progress and history.

## License / Author

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

Author: Nicholas Russell, University of Nottingham (<nicholas.russell@nottingham.ac.uk>)
