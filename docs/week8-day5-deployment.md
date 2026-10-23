# Week 8 Day 5: Deployment and Production Verification

## Deployment status

No production deployment URL or deployment manifest was found in this checkout.
In particular, the repository has no project-level Docker Compose, Render,
Railway, or Procfile configuration. No production server, database, or external
AI provider credentials were available to this review. Therefore production
deployment and HTTP verification are **not verified**; do not treat the
instructions below as evidence of a running deployment.

The Moodle source tree is in `public/`. Use that directory as the web document
root and keep Moodle's data directory outside the web root. Use a supported
PHP version and database engine for this exact Moodle revision; the Week 8
Day 3 implementation notes PHP 8.3 or newer for the current checkout. Consult
the Moodle [server requirements](https://moodledev.io/general/releases) for
the applicable PHP extensions and database versions.

## Local or hosted Moodle deployment

1. Provision a web server, HTTPS, a supported PHP runtime with Moodle's
   required extensions, and a supported MySQL/MariaDB or other Moodle database.
   Configure the web server document root to this checkout's `public/`
   directory.
2. Create a dedicated Moodle database and a writable `moodledata` directory
   outside the document root. Apply least-privilege database and filesystem
   permissions.
3. Install Moodle using the web installer or its supported CLI installer.
   Configure the site URL to the actual HTTPS URL and point Moodle data storage
   outside the public directory.
4. Keep `public/config.php`, database credentials, provider keys, backups, and
   logs out of commits and public web access. The repository `.gitignore`
   ignores root `/config.php`, not necessarily `public/config.php`; before
   creating local configuration, verify its ignore status and use the local
   `.git/info/exclude` when needed. Never put secrets in tracked documentation,
   environment files, screenshots, or command output.
5. Install/upgrade the plugins through Moodle's normal upgrade process by
   visiting **Site administration > Notifications**, or run from the repository
   root:

   ```powershell
   php public/admin/cli/upgrade.php --non-interactive
   ```

6. In **Site administration > Plugins > Local plugins > AI Tutor**, configure
   the Groq API key. Configure an OpenRouter key only if the chat completion
   fallback is required. Store keys in Moodle's protected site configuration;
   never commit them. Quiz generation calls Groq directly and does not use the
   OpenRouter fallback.
7. Add the **Cynaris Stats** block to the desired page. Sign in as a learner
   with visible grades to verify the chart; use a test account and synthetic
   data only.
8. Verify the recommendation page as a signed-in learner at
   `/local/aitutor/recommendations.php`. Ensure the test course has visible
   activities with completion tracking enabled.
9. Check Moodle's site notifications, server/PHP logs, and browser network
   responses for errors. Test missing keys, provider failure, empty
   recommendations, hidden grades, and users without relevant capabilities.
   Confirm there are no HTTP 500 responses and that sensitive values do not
   appear in logs.

## Feature-specific verification

| Feature | Available implementation | Verification requirement |
|---|---|---|
| Groq chat completion with OpenRouter fallback | `local_aitutor` service class | Exercise through an authorized integration in a non-production site with keys configured; test primary failure and fallback. No standalone learner chat page was found in this checkout. |
| AI quiz generation and import | `ai_quiz::generate_questions()` and `import_questions()` | Run focused Moodle PHPUnit tests and verify the imported questions in a test course question bank. No learner/instructor generation page was found in this checkout. |
| Learning recommendations | `/local/aitutor/recommendations.php` | Sign in as a learner and test grade visibility, completion states, empty states, and activity visibility. |
| Cynaris statistics block | `block_cynaris_stats` | Install/upgrade the plugin, add the block, and verify chart/no-grade states and grade visibility permissions. The plugin declares alpha maturity. |

Static inspection confirms the recommendation page requires login, and the
stats block checks grade-view capabilities and hidden-grade visibility. Static
inspection is not a substitute for a deployed smoke test. Do not call any
feature production-verified until the live checks above have actually passed.

## Environment variables and secrets

This checkout does not define an application `.env` contract or a deployment
environment-variable mapping for plugin secrets. Moodle admin settings store
the provider keys (`local_aitutor/groqapikey` and
`local_aitutor/openrouterapikey`). The settings are not values to copy into
deployment manifests or this repository. Database and site configuration
belongs in Moodle's deployment configuration and must remain outside version
control.

## URL and access instructions

- **Production URL:** Not supplied or discoverable from repository
  configuration; no production deployment could be verified.
- **Local URL:** Set by the local Moodle installer/site configuration. Use the
  configured site URL; this repository does not establish a default host,
  port, or web-server setup.
- **Recommendations:** `<configured Moodle base URL>/local/aitutor/recommendations.php`
  (authenticated learner).
- **Stats block:** Sign in and open a page where an administrator has placed
  the block.
- **AI Tutor settings:** Site administration > Plugins > Local plugins > AI Tutor.

## Verification record

See [week8-day5-evidence.md](./week8-day5-evidence.md) for the actual local
repository checks, the checks that could not run, and the evidence still
required from a configured Moodle site.
