<?php

defined('MOODLE_INTERNAL') || die();

class block_cynaris_stats extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_cynaris_stats');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $OUTPUT, $USER;

        $this->content = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = html_writer::tag('p', get_string('nogrades', 'block_cynaris_stats'));
            return $this->content;
        }

        $labels = [];
        $values = [];
        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname, showgrades', 'fullname ASC');
        foreach ($courses as $course) {
            if (empty($course->showgrades)) {
                continue;
            }

            $context = context_course::instance($course->id);
            if (!has_capability('moodle/grade:view', $context)) {
                continue;
            }

            $canviewhidden = has_capability('moodle/grade:viewhidden', $context);
            $coursegrades = grade_get_course_grades($course->id, $USER->id);
            if (!isset($coursegrades->grades[$USER->id])) {
                continue;
            }

            $usergrade = $coursegrades->grades[$USER->id];
            if ((!$canviewhidden && ($coursegrades->hidden || $usergrade->hidden)) ||
                    !is_numeric($usergrade->grade) ||
                    !is_numeric($coursegrades->grademin) ||
                    !is_numeric($coursegrades->grademax) ||
                    $coursegrades->grademax <= $coursegrades->grademin) {
                continue;
            }

            $grade = $usergrade->grade;
            $percentage = (($grade - $coursegrades->grademin) /
                    ($coursegrades->grademax - $coursegrades->grademin)) * 100;
            $labels[] = format_string($course->fullname);
            $values[] = round(max(0, min(100, $percentage)), 1);
        }

        if (empty($values)) {
            $this->content->text = html_writer::tag('p', get_string('nogrades', 'block_cynaris_stats'));
            return $this->content;
        }

        $chart = new \core\chart_bar();
        $chart->set_title(get_string('gradecharttitle', 'block_cynaris_stats'));
        $chart->set_labels($labels);
        $chart->add_series(new \core\chart_series(get_string('gradepercentage', 'block_cynaris_stats'), $values));
        $chart->get_yaxis(0, true)->set_max(100);
        $chart->get_yaxis(0)->set_label(get_string('gradepercentage', 'block_cynaris_stats'));

        $this->content->text = $OUTPUT->render($chart);
        return $this->content;
    }
}