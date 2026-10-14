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

        $this->content = new stdClass();
        $this->content->text = html_writer::tag('p', get_string('welcome', 'block_cynaris_stats'));
        $this->content->footer = '';

        return $this->content;
    }
}