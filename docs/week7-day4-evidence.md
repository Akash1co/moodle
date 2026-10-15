# Week 7 Day 4 Evidence: Grade Data Chart

## Implementation

The dashboard block is implemented in `public/blocks/cynaris_stats/block_cynaris_stats.php`.

- It reads active courses for the signed-in user and requests each course's final grade through Moodle's `grade_get_course_grades()` API.
- It includes only courses that display grades and for which the current user has `moodle/grade:view`. Hidden course totals and hidden grades are omitted unless the user can view hidden grades.
- Numeric grades are normalized against each course grade item's minimum and maximum, then displayed as percentages in a `\core\chart_bar` chart.
- Moodle renders the chart through its bundled Chart.js AMD integration. No external CDN or independently loaded JavaScript is needed.
- Guests and users without visible numeric grade data receive the localized `nogrades` message.
- The chart title, grade series label, and empty-state message are in `public/blocks/cynaris_stats/lang/en/block_cynaris_stats.php`.

## Automated Verification

PHP syntax check:

```powershell
php -l public/blocks/cynaris_stats/block_cynaris_stats.php
```

Result: no syntax errors detected.

## Dashboard Verification

On a configured Moodle site:

1. Run `php admin/cli/upgrade.php --non-interactive` from the repository root, or visit **Site administration > Notifications**. The CLI command requires a valid repository-root `config.php` and database connection.
2. Sign in as a learner enrolled in an active course with **Show grades** enabled. Ensure the gradebook has a visible numeric course total.
3. Add **Cynaris Stats** to the dashboard and reload the page.
4. Confirm the chart title is **Your course grades**, each eligible course is represented by a bar, and the vertical axis is labelled **Grade (%)** with a 0-100 range.
5. Check the chart's accessible data table or equivalent screen-reader output, if enabled by the site's Moodle chart renderer.
6. Sign in as a user with no visible numeric course grades and confirm the block displays **No course grade data is available yet.** A guest session should show the same fallback.
7. Confirm a hidden course total or hidden grade is not disclosed to a user without `moodle/grade:viewhidden`.

The Moodle dashboard was not exercised in this checkout; complete the steps above on a configured site and attach a screenshot before treating visual rendering as manually verified. The checkout's root configuration and database availability must be checked before running the CLI upgrade.