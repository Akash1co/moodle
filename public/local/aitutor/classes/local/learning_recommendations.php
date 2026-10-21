<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aitutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds explainable learning recommendations from visible grades and completion data.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learning_recommendations {

    /** @var float Scores below this percentage receive the highest priority. */
    private const LOW_SCORE_THRESHOLD = 60.0;

    /** @var float Scores below this percentage receive a medium priority. */
    private const DEVELOPING_SCORE_THRESHOLD = 75.0;

    /**
     * Get recommendation data for the signed-in learner.
     *
     * Only the learner's own active enrolments and visible grades are queried.
     * Activities without enabled completion tracking cannot safely be treated as incomplete.
     *
     * @return array{courses: array, recommendations: array, completionavailable: bool}
     */
    public static function for_current_user(): array {
        global $CFG, $USER;

        $userid = (int)$USER->id;
        if ($userid <= 0) {
            throw new \invalid_parameter_exception('A valid signed-in learner is required.');
        }

        require_once($CFG->libdir . '/gradelib.php');

        $courses = enrol_get_users_courses($userid, true, 'id, fullname, showgrades', 'fullname ASC');
        $inputcourses = [];
        $coursesummary = [];
        $completionavailable = false;

        foreach ($courses as $course) {
            $coursecontext = \context_course::instance($course->id);
            $canviewgrades = !empty($course->showgrades) &&
                has_capability('moodle/grade:view', $coursecontext, $userid);
            $canviewhidden = $canviewgrades &&
                has_capability('moodle/grade:viewhidden', $coursecontext, $userid);
            $coursegrade = $canviewgrades
                ? self::get_course_grade_percent((int)$course->id, $userid, $canviewhidden)
                : null;

            $completion = new \completion_info($course);
            $coursecompletionenabled = $completion->is_enabled() !== COMPLETION_DISABLED;
            $completionavailable = $completionavailable || $coursecompletionenabled;
            $coursecompleted = $completion->is_course_complete($userid);
            $moduleinputs = [];
            $trackedcount = 0;
            $completedcount = 0;
            $incompletecount = 0;
            $unavailablecount = 0;

            if ($coursecompletionenabled && !$coursecompleted) {
                $modinfo = get_fast_modinfo($course, $userid);
                foreach ($modinfo->get_cms() as $cm) {
                    if (empty($cm->uservisible) || !$completion->is_enabled($cm)) {
                        continue;
                    }

                    $trackedcount++;
                    $completiondata = $completion->get_data($cm, false, $userid);
                    $state = isset($completiondata->completionstate) && is_numeric($completiondata->completionstate)
                        ? (int)$completiondata->completionstate
                        : null;
                    if ($state !== null && !in_array($state, [
                        COMPLETION_INCOMPLETE,
                        COMPLETION_COMPLETE,
                        COMPLETION_COMPLETE_PASS,
                        COMPLETION_COMPLETE_FAIL,
                        COMPLETION_COMPLETE_FAIL_HIDDEN,
                    ], true)) {
                        $state = null;
                    }

                    if ($state === null) {
                        $unavailablecount++;
                        continue;
                    }

                    if ($state !== COMPLETION_INCOMPLETE) {
                        $completedcount++;
                        continue;
                    }

                    $incompletecount++;
                    $activitygrade = $canviewgrades
                        ? self::get_activity_grade_percent((int)$course->id, $cm, $userid, $canviewhidden)
                        : null;
                    $url = $cm->url instanceof \moodle_url ? $cm->url : null;

                    $moduleinputs[] = [
                        'id' => (int)$cm->id,
                        'name' => (string)$cm->name,
                        'url' => $url,
                        'completion_state' => $state,
                        'grade_percent' => $activitygrade,
                    ];
                }
            }

            $inputcourses[] = [
                'courseid' => (int)$course->id,
                'course_name' => (string)$course->fullname,
                'grade_percent' => $coursegrade,
                'course_completed' => $coursecompleted,
                'modules' => $moduleinputs,
            ];
            $coursesummary[] = [
                'courseid' => (int)$course->id,
                'course_name' => (string)$course->fullname,
                'grade_percent' => $coursegrade,
                'completion_enabled' => $coursecompletionenabled,
                'course_completed' => $coursecompleted,
                'tracked_modules' => $trackedcount,
                'completed_modules' => $completedcount,
                'incomplete_modules' => $incompletecount,
                'unavailable_modules' => $unavailablecount,
            ];
        }

        return [
            'courses' => $coursesummary,
            'recommendations' => self::recommend($inputcourses),
            'completionavailable' => $completionavailable,
        ];
    }

    /**
     * Apply transparent scoring rules to prepared course and activity data.
     *
     * @param array $courses Course data with completion states and optional visible grades.
     * @return array Ordered recommendations.
     */
    public static function recommend(array $courses): array {
        if (!array_is_list($courses)) {
            throw new \invalid_parameter_exception('Course recommendation data must be a list.');
        }

        $recommendations = [];
        foreach ($courses as $course) {
            if (!is_array($course) ||
                    !isset($course['courseid'], $course['course_name'], $course['modules']) ||
                    !is_int($course['courseid']) || $course['courseid'] <= 0 ||
                    !is_string($course['course_name']) || trim($course['course_name']) === '' ||
                    !is_array($course['modules']) || !array_is_list($course['modules']) ||
                    (array_key_exists('course_completed', $course) && !is_bool($course['course_completed']))) {
                throw new \invalid_parameter_exception('Invalid course recommendation data.');
            }

            $coursegrade = self::validate_grade($course['grade_percent'] ?? null);
            if (!empty($course['course_completed'])) {
                continue;
            }

            foreach ($course['modules'] as $module) {
                if (!is_array($module) ||
                        !isset($module['id'], $module['name']) ||
                        !is_int($module['id']) || $module['id'] <= 0 ||
                        !is_string($module['name']) || trim($module['name']) === '' ||
                        !array_key_exists('completion_state', $module) ||
                        ($module['completion_state'] !== null && !in_array($module['completion_state'], [
                            COMPLETION_INCOMPLETE,
                            COMPLETION_COMPLETE,
                            COMPLETION_COMPLETE_PASS,
                            COMPLETION_COMPLETE_FAIL,
                            COMPLETION_COMPLETE_FAIL_HIDDEN,
                        ], true))) {
                    throw new \invalid_parameter_exception('Invalid activity recommendation data.');
                }

                $activitygrade = self::validate_grade($module['grade_percent'] ?? null);
                $url = $module['url'] ?? null;
                if ($url !== null && !($url instanceof \moodle_url)) {
                    throw new \invalid_parameter_exception('An activity link must be a Moodle URL.');
                }

                if ($module['completion_state'] !== COMPLETION_INCOMPLETE) {
                    continue;
                }

                $grade = $activitygrade ?? $coursegrade;
                $gradesource = $activitygrade !== null ? 'activity' : ($coursegrade !== null ? 'course' : null);
                [$priority, $prioritylabel, $reason] = self::score($grade, $gradesource);

                $recommendations[] = [
                    'courseid' => $course['courseid'],
                    'course_name' => $course['course_name'],
                    'topic' => $module['name'],
                    'resource_url' => $url,
                    'completion_state' => $module['completion_state'],
                    'grade_percent' => $grade,
                    'grade_source' => $gradesource,
                    'priority' => $priority,
                    'priority_label' => $prioritylabel,
                    'reason' => $reason,
                ];
            }
        }

        usort($recommendations, static function(array $first, array $second): int {
            $prioritycomparison = $second['priority'] <=> $first['priority'];
            if ($prioritycomparison !== 0) {
                return $prioritycomparison;
            }

            $coursecomparison = strcasecmp($first['course_name'], $second['course_name']);
            return $coursecomparison !== 0 ? $coursecomparison : strcasecmp($first['topic'], $second['topic']);
        });

        return $recommendations;
    }

    /**
     * Normalize a numeric grade to a percentage while respecting its grade range.
     *
     * @param int|float $grade Raw grade.
     * @param int|float $minimum Grade minimum.
     * @param int|float $maximum Grade maximum.
     * @return float|null Percentage, or null for invalid ranges.
     */
    private static function normalize_grade($grade, $minimum, $maximum): ?float {
        if (!is_numeric($grade) || !is_numeric($minimum) || !is_numeric($maximum) ||
                (float)$maximum <= (float)$minimum) {
            return null;
        }

        $percentage = (((float)$grade - (float)$minimum) / ((float)$maximum - (float)$minimum)) * 100;
        return round(max(0, min(100, $percentage)), 1);
    }

    /**
     * Return a visible course-total percentage.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param bool $canviewhidden Whether the user can view hidden grades.
     * @return float|null Percentage when a visible numeric total is available.
     */
    private static function get_course_grade_percent(int $courseid, int $userid, bool $canviewhidden): ?float {
        $coursegrades = grade_get_course_grades($courseid, $userid);
        if (!isset($coursegrades->grades[$userid])) {
            return null;
        }

        $usergrade = $coursegrades->grades[$userid];
        if ((!$canviewhidden && (!empty($coursegrades->hidden) || !empty($usergrade->hidden))) ||
                !is_numeric($usergrade->grade)) {
            return null;
        }

        return self::normalize_grade($usergrade->grade, $coursegrades->grademin, $coursegrades->grademax);
    }

    /**
     * Return a visible module-grade percentage, if the activity has one.
     *
     * @param int $courseid Course ID.
     * @param \cm_info $cm Course module.
     * @param int $userid User ID.
     * @param bool $canviewhidden Whether the user can view hidden grades.
     * @return float|null Percentage when a visible numeric activity grade is available.
     */
    private static function get_activity_grade_percent(int $courseid, \cm_info $cm, int $userid, bool $canviewhidden): ?float {
        $grades = grade_get_grades($courseid, 'mod', $cm->modname, $cm->instance, $userid);
        foreach ($grades->items as $item) {
            if (!empty($item->scaleid) || (!$canviewhidden && !empty($item->hidden))) {
                continue;
            }

            $grade = $item->grades[$userid] ?? null;
            if (!$grade || (!$canviewhidden && !empty($grade->hidden))) {
                continue;
            }

            $percentage = self::normalize_grade($grade->grade, $item->grademin, $item->grademax);
            if ($percentage !== null) {
                return $percentage;
            }
        }

        return null;
    }

    /**
     * Validate an optional percentage.
     *
     * @param mixed $grade Percentage or null when no visible grade is available.
     * @return float|null Valid percentage.
     */
    private static function validate_grade($grade): ?float {
        if ($grade === null) {
            return null;
        }

        if ((!is_int($grade) && !is_float($grade)) || !is_finite((float)$grade) || $grade < 0 || $grade > 100) {
            throw new \invalid_parameter_exception('Grade percentages must be between zero and one hundred.');
        }

        return round((float)$grade, 1);
    }

    /**
     * Calculate a priority and learner-facing explanation.
     *
     * @param float|null $grade Visible grade percentage.
     * @param string|null $gradesource Whether the score is from an activity or course total.
     * @return array{int, string, string}
     */
    private static function score(?float $grade, ?string $gradesource): array {
        if ($grade === null) {
            return [
                60,
                'next',
                get_string('reasonnograde', 'local_aitutor'),
            ];
        }

        $formattedgrade = number_format($grade, 1);
        if ($grade < self::LOW_SCORE_THRESHOLD) {
            $reason = $gradesource === 'activity'
                ? get_string('reasonlowactivity', 'local_aitutor', $formattedgrade)
                : get_string('reasonlowcourse', 'local_aitutor', $formattedgrade);
            return [100, 'high', $reason];
        }

        if ($grade < self::DEVELOPING_SCORE_THRESHOLD) {
            $reason = $gradesource === 'activity'
                ? get_string('reasondevelopingactivity', 'local_aitutor', $formattedgrade)
                : get_string('reasondevelopingcourse', 'local_aitutor', $formattedgrade);
            return [75, 'medium', $reason];
        }

        $reason = $gradesource === 'activity'
            ? get_string('reasonstrongactivity', 'local_aitutor', $formattedgrade)
            : get_string('reasonstrongcourse', 'local_aitutor', $formattedgrade);
        return [50, 'next', $reason];
    }
}
