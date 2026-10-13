# Week 7 Day 2 Evidence — Moodle 5 Core Tables & Query Analysis

## 1. Database Connection & Table Schema Inspection

- **Method used:** Inspected Moodle's XMLDB schema files with PowerShell XML parsing. Attempted a read-only MySQL 8.0 CLI connection to `127.0.0.1:3306` as `root` without a password; MySQL returned `ERROR 1045` (access denied).
- **Runtime check:** MySQL and MySQL80 Windows services were running and port 3306 was listening. Docker Desktop's Linux engine was unavailable. No local web listener was found on the checked Moodle ports.
- **Connection configuration:** `public/config.php` is a loader for the repository-root `config.php`; that root file is absent. No Moodle database credentials were available, and none were guessed or recorded.
- **Schema verification:** XMLDB definitions were found for `mdl_course`, `mdl_grade_grades`, `mdl_course_completions`, `mdl_user`, and `mdl_logstore_standard_log`. This is repository-schema verification, not live `DESCRIBE` verification.
- **Log table note:** The current standard log-store plugin defines `mdl_logstore_standard_log`; `mdl_log` was not found as its table in the inspected checkout. A live database could still contain custom or legacy tables.
- **Query execution:** Blocked because a Moodle DB connection could not be established. No query output rows are claimed. The prepared queries and result-column layouts are in [week7-day2-db-schema.md](week7-day2-db-schema.md).

[SCREENSHOT REQUIRED: Database CLI / Table DESCRIBE output]

## 2. ER Diagram Verification

- Mermaid `erDiagram` source is included in [week7-day2-db-schema.md](week7-day2-db-schema.md).
- The diagram identifies `userid`, `course`/`courseid`, and `itemid`; `mdl_grade_items` is included as the necessary bridge from grades to courses.
- No Mermaid renderer was run in this environment. Attach a rendered view after verification.

[SCREENSHOT REQUIRED: ER Diagram rendering]

## 3. SQL Query Execution Results

### Query (a): Students + completion status

- **SQL:** Included in [week7-day2-db-schema.md](week7-day2-db-schema.md).
- **Result status:** Not executed; Moodle DB configuration is absent and the unauthenticated MySQL connection was rejected. Actual output must be captured after database access is configured.
- **Schema note:** `mdl_course_completions` stores `timecompleted`, not a `completionstate` field; the query derives a course-level completion label from that timestamp.

[SCREENSHOT REQUIRED: Query (a) execution output]

### Query (b): Average grade per course

- **SQL:** Included in [week7-day2-db-schema.md](week7-day2-db-schema.md). It joins the course grade item through `mdl_grade_items` and averages non-NULL `finalgrade` values.
- **Result status:** Not executed; Moodle DB configuration is absent and the unauthenticated MySQL connection was rejected.

[SCREENSHOT REQUIRED: Query (b) execution output]

### Query (c): Logins / activity in last 7 days

- **SQL:** Included in [week7-day2-db-schema.md](week7-day2-db-schema.md), using `mdl_logstore_standard_log` and `UNIX_TIMESTAMP() - (7 * 86400)`.
- **Result status:** Not executed; Moodle DB configuration is absent and the unauthenticated MySQL connection was rejected.

[SCREENSHOT REQUIRED: Query (c) execution output]

## 4. Git & Branching Evidence

- **Feature branch:** `feat/w7d2-3m-Akasha-G` was already active and clean at initial inspection; it is the requested branch.
- **Origin:** Configured as `https://github.com/Akash1co/moodle.git` for fetch and push.
- **Upstream:** No `upstream` remote was configured at initial inspection. The requested `moodle/moodle` fetch/rebase and origin push must be recorded only after their outcomes are known.
- **History:** The checkout is shallow. At initial inspection, `main` and the Day 2 branch pointed to `f20534726` with no divergence.
- **Day 1 preservation:** The Day 1 branch `feat/w7d1-3m-Akasha-G` contains `docs/week7-day1-cia-review.md`, `docs/week7-day1-evidence.md`, and `docs/week7-day1-plugin-architecture.md`. They are not present on the initial Day 2 branch, whose worktree had no `docs/` directory; that branch was not checked out or modified.
- **Commit/rebase/push status:** To be filled with actual commit hashes and command outcomes after the Git workflow is performed.
