# Week 8 Day 4 Release Changelog

## Release scope

This changelog summarizes the Week 8 work present in the inspected repository
and distinguishes completed work from Day 4 validation that remains blocked.
It does not claim a release, merge, database migration, or measured performance
improvement.

## Features delivered during Week 8

- **AI Tutor provider fallback (Day 1):** the `local_aitutor` integration uses
  Groq as the primary provider and OpenRouter as a fallback for retryable
  transport, rate-limit, and server errors. Provider credentials are read from
  Moodle plugin configuration and are not embedded in source.
- **MCQ generation and question-bank import (Day 2):** generated question
  payloads are validated for shape, count, distinct options, and correct-answer
  membership before being imported through Moodle's question-type APIs. Import
  validates that the destination category belongs to the selected course and
  uses a delegated transaction.
- **Rule-based recommendations and learner dashboard (Day 3):** the
  `local_aitutor` service uses current-user enrolments, visible grade data, and
  completion state to generate explainable priorities for incomplete visible
  activities. A learner-facing page and global-navigation entry show course
  performance and recommendations.
- **Grade visualization:** the `block_cynaris_stats` block presents course-grade
  percentages as a Moodle chart while checking grade visibility capabilities.

## Day 4 database and code-quality status

- **Database review:** source inspection identified repeated API-level grade
  lookups by course and, in the recommendations service, by eligible activity.
  The analysis and a reproducible profiling procedure are in
  [week8-day4-db-optimization.md](./week8-day4-db-optimization.md).
- **Indexes/schema:** no plugin-owned tables, `db/install.xml`, or
  `db/upgrade.php` were found in either custom plugin. No index or schema change
  was made because there is no measured EXPLAIN evidence justifying one.
- **Query tuning:** no production query refactor was made. Actual SQL, execution
  plans, and timings were unavailable; claiming a before/after improvement
  without them would be unsupported.
- **PR review resolution:** GitHub review threads and submitted reviews were
  checked for PRs #1–#8; none were returned. This does not prove that there is no
  informal feedback outside GitHub. PR #8 remains open.
- **PHP/code-quality validation:** no PHP files were changed for Day 4. PHP
  syntax checks and broader quality checks were not run in this session because
  the local command runner was unavailable.

## Known limitations and future scope

- Database-specific query plans, row counts, round-trip counts, and elapsed
  times remain to be collected on a configured test site.
- Performance of the Moodle grade/completion APIs may vary with enrolment
  count, activity count, Moodle version, database engine, and capability checks.
- The recommendation engine is deterministic and rule-based; it is not a
  trained model and does not use learner outcome data to validate its scoring.
- MCQ quality depends on the generated content and the configured provider;
  manual content review remains advisable before using imported questions.
- CIA/Full Stack mentor review interactions are not completed; there is no CIA
  integration or mentor tool available in this session.
- Git branch verification/switching, rebase, commits, push, and PR update remain
  pending because local Git commands could not be run safely.

## Release readiness

**Not ready to tag or release from this session.** Complete live DB profiling,
supported-runtime PHP/PHPUnit/XMLDB validation, mentor reviews, and the required
Git workflow before treating this as a verified Day 4 release.
