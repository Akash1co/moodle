<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aitutor\local;

use context;
use core_question\local\bank\question_version_status;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates validated multiple-choice questions from course content.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_quiz {

    /** @var int Number of generated questions in each set. */
    private const QUESTION_COUNT = 5;

    /**
     * Generate five validated MCQs from course description text.
     *
     * @param string $coursedescription Course content to use as the source.
     * @return array Validated question data.
     * @throws \moodle_exception If the input or provider response is invalid.
     */
    public static function generate_questions(string $coursedescription): array {
        $coursedescription = trim($coursedescription);
        if ($coursedescription === '') {
            throw new \moodle_exception('invalidquizinput', 'local_aitutor');
        }

        $response = ai_tutor::complete_groq([
            [
                'role' => 'system',
                'content' => 'You create accurate educational multiple-choice questions from the supplied course content. ' .
                    'Return only a strict JSON array containing exactly five objects. Each object must have exactly ' .
                    'these keys: "question" (a non-empty string), "options" (an array of four distinct non-empty ' .
                    'strings), and "correct_answer" (a string exactly matching one option). Do not add markdown or ' .
                    'text outside the JSON array.',
            ],
            [
                'role' => 'user',
                'content' => "Create exactly five questions using only this course content:\n\n" . $coursedescription,
            ],
        ], ['type' => 'json_object']);

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
        }

        return self::validate_questions($content);
    }

    /**
     * Decode and validate the question list returned by the provider.
     *
     * @param string $json Provider response content.
     * @return array Validated question data.
     * @throws \moodle_exception If the JSON is invalid or does not match the expected structure.
     */
    public static function validate_questions(string $json): array {
        try {
            $questions = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
        }

        return self::validate_question_data($questions);
    }

    /**
     * Validate a decoded list of question data.
     *
     * @param mixed $questions Decoded provider response or caller-supplied data.
     * @return array Validated question data.
     * @throws \moodle_exception If the question data does not match the required structure.
     */
    private static function validate_question_data(mixed $questions): array {
        if (!is_array($questions) || !array_is_list($questions) || count($questions) !== self::QUESTION_COUNT) {
            throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
        }

        foreach ($questions as $question) {
            if (!is_array($question) ||
                    count($question) !== 3 ||
                    !isset($question['question'], $question['options'], $question['correct_answer']) ||
                    !is_string($question['question']) || trim($question['question']) === '' ||
                    !is_array($question['options']) || !array_is_list($question['options']) ||
                    count($question['options']) !== 4 ||
                    !is_string($question['correct_answer']) || trim($question['correct_answer']) === '') {
                throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
            }

            foreach ($question['options'] as $option) {
                if (!is_string($option) || trim($option) === '') {
                    throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
                }
            }

            if (count(array_unique($question['options'], SORT_STRING)) !== 4 ||
                    !in_array($question['correct_answer'], $question['options'], true)) {
                throw new \moodle_exception('invalidquizresponse', 'local_aitutor');
            }
        }

        return $questions;
    }

    /**
     * Import validated questions into a course question bank category.
     *
     * @param array $questions Question data from {@see generate_questions()}.
     * @param int $courseid Course containing the question category.
     * @param int $categoryid Destination question category.
     * @return int[] Created question ids.
     * @throws \moodle_exception If the category is outside the course or cannot be edited.
     */
    public static function import_questions(array $questions, int $courseid, int $categoryid): array {
        global $DB, $USER;

        $questions = self::validate_question_data($questions);
        $category = $DB->get_record('question_categories', ['id' => $categoryid], 'id, contextid', MUST_EXIST);
        $context = context::instance_by_id($category->contextid);
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext || (int)$coursecontext->instanceid !== $courseid) {
            throw new \moodle_exception('invalidquizcategory', 'local_aitutor');
        }
        require_capability('moodle/question:add', $context);

        $transaction = $DB->start_delegated_transaction();
        $questionids = [];
        try {
            $qtype = \question_bank::get_qtype('multichoice');
            foreach ($questions as $questiondata) {
                $form = self::make_question_form($questiondata, $categoryid, $context->id);
                $question = (object)[
                    'qtype' => 'multichoice',
                    'createdby' => $USER->id,
                    'modifiedby' => $USER->id,
                    'status' => question_version_status::QUESTION_STATUS_READY,
                ];
                $savedquestion = $qtype->save_question($question, $form);
                $questionids[] = (int)$savedquestion->id;
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }

        return $questionids;
    }

    /**
     * Prepare the form data expected by Moodle's multichoice question type API.
     *
     * @param array $questiondata Validated MCQ data.
     * @param int $categoryid Question category id.
     * @param int $contextid Question category context id.
     * @return \stdClass Form data for question_type::save_question().
     */
    private static function make_question_form(array $questiondata, int $categoryid, int $contextid): \stdClass {
        $form = new \stdClass();
        $form->category = $categoryid . ',' . $contextid;
        $form->name = shorten_text($questiondata['question'], 250);
        $form->questiontext = ['text' => $questiondata['question'], 'format' => FORMAT_PLAIN];
        $form->generalfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $form->defaultmark = 1;
        $form->penalty = 0.3333333;
        $form->status = question_version_status::QUESTION_STATUS_READY;
        $form->noanswers = 4;
        $form->numhints = 0;
        $form->single = '1';
        $form->shuffleanswers = 1;
        $form->answernumbering = 'abc';
        $form->showstandardinstruction = 0;
        $form->shownumcorrect = 1;
        $form->correctfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $form->partiallycorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $form->incorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $form->answer = [];
        $form->feedback = [];
        $form->fraction = [];

        foreach ($questiondata['options'] as $index => $option) {
            $form->answer[$index] = ['text' => $option, 'format' => FORMAT_PLAIN];
            $form->feedback[$index] = ['text' => '', 'format' => FORMAT_HTML];
            $form->fraction[$index] = $option === $questiondata['correct_answer'] ? 1 : 0;
        }

        $form->hint = [];
        $form->hintclearwrong = [];
        $form->hintshownumcorrect = [];

        return $form;
    }

}
