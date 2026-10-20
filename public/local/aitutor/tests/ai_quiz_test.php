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
 * Tests for AI quiz question validation.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_quiz_test extends \advanced_testcase {

    /**
     * Valid question arrays are returned unchanged.
     */
    public function test_validate_questions_accepts_valid_array(): void {
        $questions = [];
        for ($index = 1; $index <= 5; $index++) {
            $questions[] = [
                'question' => 'Question ' . $index,
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
            ];
        }

        $this->assertSame($questions, ai_quiz::validate_questions(json_encode($questions)));
        $this->assertSame($questions, ai_quiz::validate_questions(json_encode(['questions' => $questions])));
    }

    /**
     * Invalid JSON and question structures are rejected.
     *
     * @param string $json Invalid JSON string.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalid_question_data_provider')]
    public function test_validate_questions_rejects_invalid_data(string $json): void {
        $this->expectException(\moodle_exception::class);
        ai_quiz::validate_questions($json);
    }

    /**
     * Invalid JSON samples.
     *
     * @return array
     */
    public static function invalid_question_data_provider(): array {
        $validquestion = [
            'question' => 'Question',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ];
        $fivequestions = array_fill(0, 5, $validquestion);
        $fivequestions[0]['options'] = ['A', 'B', 'C'];
        $invalidoptions = array_fill(0, 5, $validquestion);
        $invalidoptions[0]['correct_answer'] = 'E';

        return [
            'malformed JSON' => ['{'],
            'not an array' => ['{"questions":[]}'],
            'incorrect question count' => [json_encode([$validquestion])],
            'incorrect option count' => [json_encode($fivequestions)],
            'answer not in options' => [json_encode($invalidoptions)],
        ];
    }
}
