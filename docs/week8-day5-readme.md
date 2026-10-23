# EdTech Moodle AI Plugin Suite

## Overview

This internship project extends a Moodle source checkout with the Cynaris
Solutions `local_aitutor` plugin and `block_cynaris_stats` block. The features
combine provider-backed AI services, validated multiple-choice question
generation/import, transparent rule-based learning recommendations, and
grade analytics using Moodle APIs.

The custom Moodle source is under `public/`; documentation is under `docs/`.
This is a Moodle fork with plugins, not a separately packaged web application.

## Features

- **AI Tutor provider service:** Groq is the primary chat-completion provider.
  OpenRouter is a fallback for the general completion service when Groq is
  unavailable and a fallback key is configured. Provider credentials are set
  in Moodle administration.
- **AI quiz generation:** Generates exactly five multiple-choice questions
  from supplied course text, validates the response structure, and can import
  the questions into a course question-bank category using Moodle's question
  APIs and a delegated database transaction.
- **Learning recommendations:** An authenticated dashboard at
  `/local/aitutor/recommendations.php` summarizes eligible course grades and
  completion, then prioritizes visible incomplete activities using documented
  deterministic rules. This is not an AI-generated recommendation model.
- **Cynaris statistics block:** Renders a course-grade chart for the current
  learner where grades are visible and Moodle capabilities permit access.

**Current interface limitation:** In the inspected source, the AI Tutor and
quiz generation/import are backend services covered by tests; no learner-facing
chat screen or instructor-facing quiz-generation page was found. Do not
represent those flows as a clickable production UI until such a UI is
implemented or provided by the deployment.

## Technology

- PHP and Moodle Core APIs
- Groq chat completions API; optional OpenRouter fallback for general chat
  completions
- MySQL/MariaDB or another database supported by this Moodle revision
- Moodle question bank, grade, enrolment, completion, navigation, and chart APIs
- HTML5, CSS3, Moodle output/rendering APIs, and JavaScript as used by Moodle

## Requirements

- This repository's Moodle revision and a PHP version compatible with it. The
  Week 8 Day 3 implementation documentation identifies PHP 8.3 or newer for
  the current checkout; confirm against the current Moodle server requirements.
- A Moodle-supported database engine/version, web server, and required PHP
  extensions. Check the official [Moodle server requirements](https://moodledev.io/general/releases)
  for the exact revision.
- A configured Moodle site with `public/` as its web root and a
  `moodledata` directory outside the web root.
- A Groq API key to use AI generation. An OpenRouter key is optional and only
  provides fallback for the general completion service.
- Test courses, an instructor account with question-bank permissions, and a
  learner account with safe synthetic grades/completion data for manual checks.

## Local setup

1. Clone this repository and provision the supported PHP, database, and web
   server dependencies for the checked-out Moodle revision.
2. Configure the web server document root to `public/`. Create the Moodle
   database and an external, writable `moodledata` directory.
3. Install Moodle through its web installer or its supported CLI installer.
   Ensure the resulting `public/config.php` is not tracked. The repository's
   root `.gitignore` ignores `/config.php` at the repository root; confirm
   `public/config.php` is ignored locally, adding it to `.git/info/exclude` if
   required. Never commit credentials.
4. Run the site upgrade from the repository root:

   ```powershell
   php public/admin/cli/upgrade.php --non-interactive
   ```

   Or use **Site administration > Notifications**.
5. Configure **Site administration > Plugins > Local plugins > AI Tutor**.
   Store provider keys in Moodle admin settings; do not add them to files or
   source control.
6. Add the **Cynaris Stats** block to a page and create a test course with
   completion tracking and visible grade data.
7. Sign in as a learner and open
   `/local/aitutor/recommendations.php`.

See [week8-day5-deployment.md](./week8-day5-deployment.md) for deployment
verification and production caveats.

## Focused tests

From the repository root on a configured Moodle checkout:

```powershell
php public/vendor/bin/phpunit --filter learning_recommendations public/local/aitutor/tests/learning_recommendations_test.php
php public/vendor/bin/phpunit --filter ai_quiz public/local/aitutor/tests/ai_quiz_test.php
php public/vendor/bin/phpunit --filter ai_quiz_import public/local/aitutor/tests/ai_quiz_import_test.php
```

These tests require Moodle's installed vendor dependencies and its supported
test setup. Use synthetic data and avoid real learner records. See
[week8-day5-evidence.md](./week8-day5-evidence.md) for the validation actually
performed for this documentation change.

## Production deployment

No Docker, Render, Railway, or other project deployment configuration and no
production URL were present in the inspected checkout. Use the deployment
procedure in [week8-day5-deployment.md](./week8-day5-deployment.md); complete a
real deployment and record its URL and smoke-test evidence before claiming
production availability.

## Limitations and roadmap

### Known limitations

- No production host, URL, database connection, or deployment credentials were
  available for this task; production status is unverified.
- No first-party chat or quiz-generation UI was found. AI generation and import
  are callable services, not a complete instructor workflow.
- Recommendations use explicit rules, not a trained or generative model.
- Recommendations depend on activity completion tracking and grade visibility.
- AI quiz generation requires an external Groq service and has provider limits,
  network dependency, and output quality risks. Generated items must be
  reviewed by an instructor before use.
- The stats block is marked alpha (`0.1`); validate it on the target Moodle
  version before production rollout.
- Query plans, performance measurements, and external provider behavior have
  not been verified against production data.

### Roadmap

1. Implement role-appropriate Moodle forms/pages for instructor quiz generation
   and learner AI chat, with capability checks, validation, rate limits, and
   accessibility review.
2. Add a supported deployment recipe and non-secret environment/configuration
   guidance after selecting a hosting target.
3. Run test coverage, compatibility checks, and an authenticated smoke test on
   the chosen Moodle/PHP/database versions.
4. Profile real Moodle SQL/API workloads with privacy-safe test data before
   considering query or schema changes.
5. Conduct accessibility, security, privacy, and instructor review of
   generated content and recommendation outcomes.

## Viva preparation

**Why use Moodle APIs instead of direct grade SQL?** Moodle APIs apply
capability, visibility, and version-specific data rules. The plugin avoids
duplicating core grade schema logic.

**How do recommendations work?** A deterministic score/rule service uses
visible grades and incomplete, visible, tracked activities. Completed work and
unavailable completion states are excluded; reasons and priority are rendered
by the page.

**How are generated questions protected?** The service validates a strict
five-question/four-option response, checks the destination question category's
course context, requires `moodle/question:add`, and saves within a delegated
transaction.

**What happens when Groq is unavailable?** General chat completion tries
OpenRouter when configured. Quiz generation calls Groq directly and reports a
request failure instead of silently switching providers.

**What evidence is missing for production sign-off?** A real deployment URL,
smoke tests and HTTP status/log checks, configured database/provider
verification, test output from the deployment environment, and actual mentor
reviews.
