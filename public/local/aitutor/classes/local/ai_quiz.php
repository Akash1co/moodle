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

}
