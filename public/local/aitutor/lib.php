<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Add the learner recommendations page to the global navigation.
 *
 * @param global_navigation $navigation Navigation tree.
 */
function local_aitutor_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $navigation->add(
        get_string('recommendations', 'local_aitutor'),
        new moodle_url('/local/aitutor/recommendations.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_aitutor_recommendations'
    );
}
