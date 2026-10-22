<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

use local_aitutor\local\learning_recommendations;

require('../../config.php');

require_login();

$url = new moodle_url('/local/aitutor/recommendations.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('recommendations', 'local_aitutor'));
$PAGE->set_heading(get_string('recommendations', 'local_aitutor'));

$data = learning_recommendations::for_current_user();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('recommendationsheading', 'local_aitutor'));

if (!empty($data['courses'])) {
    echo $OUTPUT->heading(get_string('performanceheading', 'local_aitutor'), 3);
    $rows = [];
    foreach ($data['courses'] as $course) {
        $grade = $course['grade_percent'] === null
            ? get_string('notavailable')
            : number_format($course['grade_percent'], 1) . '%';
        if ($course['course_completed']) {
            $progress = get_string('coursecompleted', 'local_aitutor');
        } else if (!$course['completion_enabled']) {
            $progress = get_string('completionnotenabled', 'local_aitutor');
        } else if ($course['tracked_modules'] === 0) {
            $progress = get_string('notrackedactivities', 'local_aitutor');
        } else {
            $progressdata = (object)[
                'completed' => $course['completed_modules'],
                'tracked' => $course['tracked_modules'],
            ];
            $progress = get_string('completionprogress', 'local_aitutor', $progressdata);
        }

        $rows[] = [
            format_string($course['course_name']),
            $grade,
            $progress,
        ];
    }

    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('grade'),
        get_string('completion'),
    ];
    $table->data = $rows;
    echo html_writer::table($table);
}

if (empty($data['recommendations'])) {
    if (empty($data['courses'])) {
        $emptymessage = get_string('noenrolledcourses', 'local_aitutor');
    } else if (empty($data['completionavailable'])) {
        $emptymessage = get_string('completionnotavailable', 'local_aitutor');
    } else {
        $emptymessage = get_string('norecommendations', 'local_aitutor');
    }
    echo $OUTPUT->notification($emptymessage, \core\output\notification::NOTIFY_INFO);
} else {
    echo $OUTPUT->heading(get_string('recommendationlistheading', 'local_aitutor'), 3);
    foreach ($data['recommendations'] as $recommendation) {
        $content = html_writer::tag('h4', format_string($recommendation['topic']));
        $content .= html_writer::tag('p', format_string($recommendation['course_name']));
        $content .= html_writer::tag('p', s($recommendation['reason']));

        if ($recommendation['grade_percent'] === null) {
            $performance = get_string('noperformancecontext', 'local_aitutor');
        } else {
            $gradesource = get_string('gradesource' . $recommendation['grade_source'], 'local_aitutor');
            $performance = get_string(
                'performancecontext',
                'local_aitutor',
                (object)[
                    'grade' => number_format($recommendation['grade_percent'], 1) . '%',
                    'source' => $gradesource,
                ]
            );
        }
        $content .= html_writer::tag('p', s($performance));

        $priority = get_string('priority' . $recommendation['priority_label'], 'local_aitutor');
        $content .= html_writer::tag(
            'p',
            html_writer::tag('strong', s(get_string('prioritylabel', 'local_aitutor') . ': ')) .
                s($priority . ' (' . $recommendation['priority'] . '/100)')
        );

        if ($recommendation['resource_url'] instanceof moodle_url) {
            $content .= html_writer::div(
                html_writer::link(
                    $recommendation['resource_url'],
                    get_string('openactivity', 'local_aitutor'),
                    ['class' => 'btn btn-primary']
                )
            );
        }

        echo html_writer::div(
            html_writer::div($content, 'card-body'),
            'card mb-3'
        );
    }
}

echo $OUTPUT->footer();
