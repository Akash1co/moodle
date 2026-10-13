# CIA Review — Week 7 Day 2

> Review log status: these are AI-assisted self-reviews prepared for mentor discussion. No external mentor response was provided or is represented as having occurred. Both mentor review requests remain pending.

## Interaction 1: Database Schema & ER Diagram Review

- **Review request:** Full Stack Mentor review of 5 core Moodle tables schema analysis and ER relationships.
- **Review status:** Self-review completed; external mentor response pending.
- **Feedback (self-review):** The checked-in schema identifies the modern standard log table as `mdl_logstore_standard_log`. `mdl_grade_grades.itemid` refers to `mdl_grade_items.id`, and the course relationship continues through `mdl_grade_items.courseid`; it is not a direct `grade_grades.courseid` relationship. `mdl_course_completions` uses `course` and `timecompleted`, and does not define `completionstate`. The completion user/course links are represented as logical relationships because the inspected XMLDB definition does not declare foreign keys for them.
- **Changes made:** Refined the Mermaid diagram to show the grade-items bridge, correct relationship column names, and nullable log `courseid`; documented the course-completion field distinction and XMLDB relationship caveat.

## Interaction 2: SQL Query Optimization & Verification Review

- **Review request:** Mentorship code review of SQL queries for completion tracking, grade aggregation, and 7-day activity window.
- **Review status:** Self-review completed; external mentor response and live query verification pending.
- **Feedback (self-review):** Query shapes use Moodle relationship paths, an active-enrolment and student-role check for the completion report, the course-level grade item for averaging, and a bounded timestamp range plus result limit for activity. XMLDB indexes include completion `(userid, course)`, grade `(userid, itemid)`, log `timecreated`, and a compound log user/context/time index. Performance has not been measured with `EXPLAIN` or live data because no Moodle database connection is configured; index presence alone is not proof of query performance.
- **Changes made:** Added bounded/orderable report queries, excluded deleted/suspended users where applicable, used a seven-day Unix timestamp cutoff, and documented execution as blocked rather than presenting fabricated results. Live optimization and mentor feedback remain to be completed after database access is restored.
