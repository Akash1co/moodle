# Week 8 Day 4: Database Query Optimization and EXPLAIN Analysis

## Scope and evidence status

This is a source-level workload review of `local_aitutor` and `block_cynaris_stats`.
It is not a database benchmark. The local command runner and Moodle database were
unavailable during this review, so no SQL trace, `EXPLAIN`/`EXPLAIN ANALYZE`,
execution-time measurement, PHPUnit run, or database-backed XMLDB validation was
performed. Do not interpret the workload estimates below as measured query counts
or timings.

The inspected plugin files contain no `db/install.xml` or `db/upgrade.php`, and
neither plugin declares its own persistent tables. Production code uses Moodle's
enrolment, grade, completion, and question-bank APIs. These APIs own the SQL and
schema details, which may vary with Moodle version, database engine, capabilities,
and site data. No index was added: there is no plugin table to index, and adding
an index to a Moodle core table without a measured plan and a core-supported
schema migration would be unsafe.

## High-frequency query paths

| Path | Source | Workload shape / bottleneck to measure | Source-level conclusion |
|---|---|---|---|
| Learner enrolment and course grades | `public/local/aitutor/classes/local/learning_recommendations.php`, `for_current_user()` | One enrolment API call, then a course-grade API call for each enrolled course where grade visibility permits it. Work can grow with the learner's course count. | Current-user scope and Moodle capability checks are preserved. Confirm actual SQL and plans with Moodle DB logging; no raw SQL is issued by this plugin code. |
| Per-activity grade lookup | `public/local/aitutor/classes/local/learning_recommendations.php`, `get_activity_grade_percent()` called for incomplete visible activities | Grade API work can grow with the number of visible, incomplete, tracked activities in courses where the learner can view grades. This is the strongest candidate for repeated-query profiling. | Moodle's grade API is used. Do not replace it with an unverified join or add indexes to core tables based on this call graph alone. |
| Grade chart course loop | `public/blocks/cynaris_stats/block_cynaris_stats.php`, `get_content()` | One enrolment API call followed by a course-grade API call for each course with grades enabled and visible. The block may run on pages where it is displayed. | It checks `showgrades` and `moodle/grade:view` before fetching grades and omits hidden data unless `moodle/grade:viewhidden` is granted. Measure with the actual block placement and Moodle SQL log. |
| Question category lookup during import | `public/local/aitutor/classes/local/ai_quiz.php`, `import_questions()` | One category lookup by `question_categories.id`, followed by Moodle question-type save operations for five questions. The lookup is a primary-key lookup by design; save operations are Moodle-owned. | The plugin does not perform repeated lookup queries per question. No additional index is justified from source inspection. |

These are API-level paths, not literal SQL statements. The actual SQL, bindings,
joins, query plans, and number of database round-trips must be captured from the
configured Moodle instance. The quiz import test also contains SQL assertions,
but those execute only in tests and are not production request queries.

## EXPLAIN and timing results

| Path | Before plan / time | After plan / time | Result |
|---|---|---|---|
| Enrolments and course grades | Not captured | Not captured | Blocked: no local DB connection or SQL trace was available. |
| Activity grade lookups | Not captured | Not captured | Blocked: actual grade API SQL and DB engine were unavailable. |
| Stats block course-grade loop | Not captured | Not captured | Blocked: no configured Moodle request/SQL log was available. |
| Question category lookup | Not captured | Not captured | Static review only; no query plan was collected. |

No before/after timing, full-table-scan finding, or index benefit is claimed.
Static source inspection cannot establish those facts.

## XMLDB and index decision

- **`local_aitutor`:** no plugin `db/install.xml` or `db/upgrade.php` exists; no plugin tables are declared.
- **`block_cynaris_stats`:** no plugin `db/install.xml` or `db/upgrade.php` exists; no plugin tables are declared.
- **Index additions:** none. Existing Moodle core indexes and the database's chosen plans were not verified against a live database. A plugin upgrade step is therefore not applicable.
- **Upgrade version changes:** none. No schema migration was identified to warrant a plugin version bump.

Do not introduce speculative indexes into core Moodle schemas from a contributed
plugin. If later profiling proves a missing index on a plugin-owned table, add
the index to that plugin's XMLDB install definition and an idempotent,
version-gated `xmldb` upgrade step, then validate both fresh install and upgrade
paths against supported Moodle databases.

## Reproducible follow-up procedure

On the configured development site:

1. Record the Moodle revision, database engine/version, PHP version, and a
   non-sensitive description of the dataset size. Never include credentials or
   learner-identifying data in evidence.
2. Enable Moodle's supported developer SQL/performance logging for a test user.
   Exercise the learner recommendations page, then a page containing the stats
   block. Capture the logged statements, elapsed times, and query counts.
3. In the matching database's read-only query console, run that engine's
   `EXPLAIN` for the captured statements using the captured bound values or safe
   representative test data. Use `EXPLAIN ANALYZE` only in an isolated test
   environment where executing the statement is safe.
4. Record actual plans (scan/access method, selected index, estimated/actual
   rows, loops, and execution time) in the table above, with sensitive values
   redacted. Compare plans before and after a justified code/schema change.
5. If a repeated Moodle API lookup is demonstrated to dominate, first check
   Moodle's supported bulk APIs and caching patterns. Retain visibility,
   completion, and authorization semantics. Only propose a plugin-owned index
   when a plugin-owned query and measured missing-index bottleneck exist.
6. Run the focused Moodle tests, PHP lint, and XMLDB checks on a supported PHP
   runtime and configured test database, and append actual output to
   `week8-day4-evidence.md`.

## Conclusion

The actionable outcome from this source review is a profiling plan, not a
database change. No query plan or timing evidence supports claiming an
optimization or index addition yet. The repeated per-course/per-activity API
calls are candidates for measurement; any refactor should follow that evidence
and preserve the existing Moodle access checks.
