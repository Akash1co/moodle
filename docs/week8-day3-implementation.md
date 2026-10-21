# Week 8 Day 3: Grade- and Completion-Based Learning Recommendations

## Feature overview

The AI Tutor local plugin now provides an authenticated **Learning recommendations** page. It recommends visible, incomplete course activities and gives each recommendation a priority, the grade context used, and an explanation. The implementation is a deterministic, rule-based recommendation engine; it does not call Groq, OpenRouter, or a trained AI/ML model.

The page is linked from Moodle's global navigation and can also be opened at `/local/aitutor/recommendations.php`.

## Architecture and changed files

- `public/local/aitutor/classes/local/learning_recommendations.php` collects learner data through Moodle APIs and contains the reusable, validated scoring rules.
- `public/local/aitutor/recommendations.php` renders the course-grade/completion summary, recommendations, and empty states for the current signed-in learner.
- `public/local/aitutor/lib.php` adds the recommendations page to the global navigation for signed-in, non-guest users.
- `public/local/aitutor/lang/en/local_aitutor.php` contains the page labels, explanations, priorities, and empty-state messages.
- `public/local/aitutor/tests/learning_recommendations_test.php` exercises scoring, completion filtering, missing data, empty data, invalid data, and multiple courses.
- `public/local/aitutor/version.php` increments the plugin version so Moodle discovers the code update.

No schema, external service, credentials, or dependency changes are required.

## How the recommendation rules work

1. Only a learner's active course enrolments are queried.
2. A course grade is read only when the course shows grades and the learner has `moodle/grade:view`. Hidden course totals and activity grades are omitted unless `moodle/grade:viewhidden` is available.
3. A course module is considered only when it is visible to the learner and activity completion tracking is enabled. Courses Moodle marks complete are excluded. Unknown activity completion states are not treated as incomplete. Any non-zero activity completion state, including completed-pass and completed-fail states, is excluded from recommendations.
4. For an incomplete activity, a visible numeric activity grade is preferred; otherwise, the visible course total is used. Grades are normalized to 0–100% using their Moodle grade range. If neither is available, the recommendation uses a neutral no-grade explanation.
5. Scores below 60% receive priority **100 (High)**; scores from 60% to below 75% receive **75 (Medium)**; scores of 75% or higher receive **50 (Next step)**. With no visible score, the priority is **60 (Next step)**. Results sort by priority descending, then course and activity name.
6. The page does not change grades, completion records, or learner data.

## Data source and output shape

The service uses `enrol_get_users_courses()`, Moodle's grade APIs (`grade_get_course_grades()` and `grade_get_grades()`), `get_fast_modinfo()`, and `completion_info`. It reads only the current `$USER->id`; `for_current_user()` accepts no learner ID, and the page accepts no learner ID from the request. Moodle's enrolment API filters hidden courses according to `moodle/course:viewhiddencourses`, and module recommendations require the module to be visible to that current user.

`learning_recommendations::for_current_user()` returns a PHP array containing:

- `courses`: course ID/name, visible grade percentage (or `null`), completion-enabled and course-completed flags, and tracked/completed/incomplete/unavailable module counts.
- `recommendations`: course ID/name, topic, optional Moodle activity URL, incomplete completion state, grade percentage/source (or `null`), numeric priority, priority label, and a localized reason.
- `completionavailable`: whether at least one enrolled course has completion tracking enabled.

No HTTP/JSON API endpoint was added; the authenticated Moodle page consumes this service directly.
The service has no arbitrary user-ID argument, preventing a page caller from selecting another learner's grades or completion records.

## Setup and testing

On a configured Moodle checkout, run the upgrade from the repository root:

```powershell
php public/admin/cli/upgrade.php --non-interactive
```

The command requires the site's `public/config.php` and a working Moodle database. Alternatively, visit **Site administration > Notifications** on the configured site. No database migration is added by this feature.

Run the focused PHPUnit test with Moodle's installed test runner:

```powershell
php public/vendor/bin/phpunit --filter learning_recommendations public/local/aitutor/tests/learning_recommendations_test.php
```

The test runner requires a compatible Moodle/PHPUnit installation and PHP 8.3 or newer for this checkout.
The feature is covered by tests for low and high scores, completed courses, completed and failed-but-completed activities, missing grades, unknown completion, empty input, invalid input, and different performance across multiple courses.

## Manual demonstration

1. Configure Moodle and upgrade the `local_aitutor` plugin.
2. Enrol a learner in a course with **Show grades** enabled and activity completion tracking turned on.
3. Add an incomplete visible activity. Add visible assessment/module grades to exercise the activity-score rule; otherwise the visible course total is used. No available grade is a supported case.
4. Sign in as the learner and open **Learning recommendations** from global navigation, or visit `/local/aitutor/recommendations.php`.
5. Confirm the grade/completion summary, priority and reason for each incomplete activity, and that completed activities are absent. Also check the no-enrolment, completion-disabled, and no-incomplete-activity empty states as applicable.

## Limitations and future improvements

- These transparent rules are not a trained model and do not call an AI service.
- Activity grade coverage depends on visible numeric gradebook items; otherwise the course total or a no-grade explanation is used.
- Content with completion tracking disabled is not recommended because its completion state cannot be verified.
- Current behavior recommends Moodle activities as learning resources; curated external resources, learner feedback, and validated outcome data could improve future recommendations.
