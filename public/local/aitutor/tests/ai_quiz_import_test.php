<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aitutor;

use local_aitutor\local\ai_quiz;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for importing generated questions into Moodle.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_quiz_import_test extends \advanced_testcase {

    /**
     * Load the quiz API used to build an attempt in the integration test.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    }

    /**
     * Imported questions can be added to and correctly answered in a quiz attempt.
     */
    public function test_import_questions_and_attempt_quiz(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $context = \context_module::instance($qbank->cmid);
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')
            ->create_question_category(['contextid' => $context->id]);

        $questions = [];
        for ($index = 1; $index <= 5; $index++) {
            $questions[] = [
                'question' => 'Generated question ' . $index,
                'options' => ['Correct', 'Wrong one', 'Wrong two', 'Wrong three'],
                'correct_answer' => 'Correct',
            ];
        }

        $questionids = ai_quiz::import_questions($questions, (int)$course->id, (int)$category->id);
        $this->assertCount(5, $questionids);
        foreach ($questionids as $questionid) {
            $this->assertTrue($DB->record_exists_sql(
                'SELECT 1
                   FROM {question_bank_entries} qbe
                   JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  WHERE qbe.questioncategoryid = :categoryid
                    AND qv.questionid = :questionid',
                ['categoryid' => $category->id, 'questionid' => $questionid]
            ));
            $this->assertSame(4, $DB->count_records('question_answers', ['question' => $questionid]));
            $this->assertSame(1.0, (float)$DB->get_field('question_answers', 'fraction', [
                'question' => $questionid,
                'answer' => 'Correct',
            ]));
        }

        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 5,
        ]);
        foreach ($questionids as $questionid) {
            quiz_add_quiz_question($questionid, $quiz);
        }

        $student = $this->getDataGenerator()->create_user();
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id, $student->id);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $student->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
        $responses = [];
        foreach ($attemptobj->get_slots() as $slot) {
            $questionid = $attemptobj->get_question_attempt($slot)->get_question_id();
            $answerid = $DB->get_field('question_answers', 'id', [
                'question' => $questionid,
                'fraction' => 1,
            ], MUST_EXIST);
            $responses[$slot] = ['answer' => (string)$answerid];
        }
        $attemptobj->process_submitted_actions($timenow, false, $responses);
        $attemptobj->process_submit($timenow, false);
        $attemptobj->process_grade_submission($timenow);

        $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->is_finished());
        $this->assertSame(5.0, (float)$attemptobj->get_sum_marks());
    }

    /**
     * Question categories from other courses cannot receive imported questions.
     */
    public function test_import_rejects_category_from_another_course(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $courseone = $this->getDataGenerator()->create_course();
        $coursetwo = $this->getDataGenerator()->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $coursetwo->id]);
        $context = \context_module::instance($qbank->cmid);
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')
            ->create_question_category(['contextid' => $context->id]);

        $this->expectException(\moodle_exception::class);
        ai_quiz::import_questions($this->valid_questions(), (int)$courseone->id, (int)$category->id);
    }

    /**
     * Return a structurally valid sample question set.
     *
     * @return array
     */
    private function valid_questions(): array {
        $questions = [];
        for ($index = 1; $index <= 5; $index++) {
            $questions[] = [
                'question' => 'Generated question ' . $index,
                'options' => ['Correct', 'Wrong one', 'Wrong two', 'Wrong three'],
                'correct_answer' => 'Correct',
            ];
        }

        return $questions;
    }
}
