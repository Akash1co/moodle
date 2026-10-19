<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aitutor', get_string('pluginname', 'local_aitutor'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aitutor/groqapikey',
        get_string('groqapikey', 'local_aitutor'),
        get_string('groqapikey_desc', 'local_aitutor'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aitutor/openrouterapikey',
        get_string('openrouterapikey', 'local_aitutor'),
        get_string('openrouterapikey_desc', 'local_aitutor'),
        ''
    ));
}