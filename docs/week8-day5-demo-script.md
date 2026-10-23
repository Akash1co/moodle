# Week 8 Day 5: Three-Minute Demo Script and EOD PR Note

## Recording prerequisites

This script is a recording plan, not evidence that a video was recorded. No
production URL or video link was supplied. The current checkout has no
first-party chat or quiz-generation UI, so demonstrate quiz generation through
an authorized development/test harness if available; otherwise show the
service and clearly state that the UI workflow remains future work. Never show
real learner data or provider credentials.

## Three-minute walkthrough

| Time | Screen / action | Narration |
|---|---|---|
| 0:00–0:25 | Title card, Moodle test site, repository/plugin overview. | “This is the Cynaris Moodle AI Extension suite: an AI Tutor provider service, validated quiz-generation and question-import services, a learner recommendation dashboard, and a grade analytics block. This demonstration uses a test site and synthetic learner data.” |
| 0:25–1:15 | Use the available development harness to generate a quiz from sample course text; inspect its validated output and, if the test workflow is available, show the resulting items in the test question bank. | “The quiz service requests five questions from Groq, validates exactly four distinct options and the correct answer, checks the destination category and question-add permission, then saves through Moodle's question API inside a delegated transaction. In this checkout these are backend services; there is not yet a first-party instructor generation page.” |
| 1:15–1:55 | Sign in as the synthetic learner and open `/local/aitutor/recommendations.php`. Show a recommendation, priority/reason, grade/completion summary, and an empty state if prepared. | “Recommendations are transparent rules, not generated AI. The service uses current-user enrolment, grade and completion APIs, respects visibility/capabilities, and links only to eligible incomplete activities.” |
| 1:55–2:20 | Show a page containing Cynaris Stats with synthetic grades, then a no-grade state if prepared. | “The block uses Moodle grade and chart APIs and omits data when grade visibility rules do not allow it.” |
| 2:20–2:50 | Briefly show provider settings with values concealed, then relevant service/test code and Day 4 query analysis. | “Groq is primary for chat completions, with optional OpenRouter fallback for that service. Quiz generation currently calls Groq directly. We use Moodle APIs rather than raw grade queries; the previous source-level review identified paths to profile but did not claim database benchmarks or indexes.” |
| 2:50–3:00 | Closing slide with deployment and test status. | “This checkout has no verified production deployment URL. Deployment smoke tests, a live video link, and mentor sign-off must be added after those steps are completed.” |

## Technical talking points

- Moodle enrolment, grade, completion, capabilities, question-bank,
  transaction, navigation, and chart APIs are used instead of direct core-table
  queries.
- Grade visibility and activity completion affect recommendation eligibility.
- Quiz response validation and course-category authorization happen before
  question import.
- Day 4 query discussion is a workload/source review only; no live `EXPLAIN`,
  timing, or query-count measurements were captured.

## Final EOD PR submission note (draft)

**Title:** Week 8 Day 5 — Moodle AI plugin documentation and deployment readiness

**Summary**

- Documented local/hosted Moodle setup, AI provider configuration, security
  considerations, feature-specific smoke tests, and current deployment
  limitations.
- Added a suite README covering architecture, setup, tests, limitations,
  roadmap, and viva preparation.
- Drafted a three-minute walkthrough script and verification evidence guide.
- Recorded mentor review status without claiming interactions that have not
  occurred.

**Validation:** Git status/branch/remotes/recent commits were inspected before
changes. See [week8-day5-evidence.md](./week8-day5-evidence.md) for exact
validation outcomes. Production deployment and HTTP 500 checks remain
unverified.

- **Demo video:** Not recorded / link pending.
- **Production URL:** Not provided / deployment pending.
- **PR URL:** Pending branch push and PR creation.
- **CIA / Full Stack mentor review:** Pending two actual review interactions.

Update this note with actual links and test results before submitting the EOD
PR. Do not submit placeholders as completed work.
