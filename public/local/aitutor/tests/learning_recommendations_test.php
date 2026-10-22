<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aitutor;

use local_aitutor\local\learning_recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for grade- and completion-based learning recommendations.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class learning_recommendations_test extends \advanced_testcase {

    /**
     * Low activity performance raises the priority and explains why.
     */
    public function test_low_score_incomplete_activity_is_high_priority(): void {
        $recommendations = learning_recommendations::recommend([
            $this->course(72.0, [
                $this->module('Fractions', COMPLETION_INCOMPLETE, 42.0),
            ]),
        ]);

        $this->assertCount(1, $recommendations);
        $this->assertSame(100, $recommendations[0]['priority']);
        $this->assertSame('high', $recommendations[0]['priority_label']);
        $this->assertSame(42.0, $recommendations[0]['grade_percent']);
        $this->assertSame('activity', $recommendations[0]['grade_source']);
        $this->assertStringContainsString('42.0%', $recommendations[0]['reason']);
    }

    /**
     * Strong performance does not prevent recommending unfinished next content.
     */
    public function test_high_score_incomplete_activity_is_a_next_step(): void {
        $recommendations = learning_recommendations::recommend([
            $this->course(91.0, [
                $this->module('Advanced fractions', COMPLETION_INCOMPLETE, 88.0),
            ]),
        ]);

        $this->assertCount(1, $recommendations);
        $this->assertSame(50, $recommendations[0]['priority']);
        $this->assertSame('next', $recommendations[0]['priority_label']);
        $this->assertSame('activity', $recommendations[0]['grade_source']);
    }

    /**
     * Completed and failed-but-completed activities are never suggested as incomplete.
     */
    public function test_completed_activities_are_excluded(): void {
        $recommendations = learning_recommendations::recommend([
            $this->course(45.0, [
                $this->module('Completed activity', COMPLETION_COMPLETE, 35.0),
                $this->module('Passed activity', COMPLETION_COMPLETE_PASS, 95.0),
                $this->module('Failed but complete activity', COMPLETION_COMPLETE_FAIL, 25.0),
            ]),
        ]);

        $this->assertSame([], $recommendations);
    }

    /**
     * Activities are not recommended when Moodle marks the whole course complete.
     */
    public function test_completed_course_is_excluded(): void {
        $course = $this->course(45.0, [
            $this->module('Incomplete activity in completed course', COMPLETION_INCOMPLETE, 35.0),
        ]);
        $course['course_completed'] = true;

        $this->assertSame([], learning_recommendations::recommend([$course]));
    }

    /**
     * Missing grades use a neutral, explainable priority.
     */
    public function test_missing_grade_uses_neutral_reason(): void {
        $recommendations = learning_recommendations::recommend([
            $this->course(null, [
                $this->module('Ungraded topic', COMPLETION_INCOMPLETE),
            ]),
        ]);

        $this->assertCount(1, $recommendations);
        $this->assertNull($recommendations[0]['grade_percent']);
        $this->assertNull($recommendations[0]['grade_source']);
        $this->assertSame(60, $recommendations[0]['priority']);
        $this->assertSame('next', $recommendations[0]['priority_label']);
        $this->assertStringContainsString('No visible grade', $recommendations[0]['reason']);
    }

    /**
     * Missing completion information is not treated as proof that content is incomplete.
     */
    public function test_missing_completion_state_is_excluded(): void {
        $recommendations = learning_recommendations::recommend([
            $this->course(30.0, [
                $this->module('Unknown completion', null),
            ]),
        ]);

        $this->assertSame([], $recommendations);
    }

    /**
     * Empty learner data produces a useful empty result.
     */
    public function test_empty_course_data_returns_no_recommendations(): void {
        $this->assertSame([], learning_recommendations::recommend([]));
    }

    /**
     * A learner without active course enrolments gets an empty service result.
     */
    public function test_for_current_user_returns_empty_without_active_courses(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $data = learning_recommendations::for_current_user();

        $this->assertSame([], $data['courses']);
        $this->assertSame([], $data['recommendations']);
        $this->assertFalse($data['completionavailable']);
    }

    /**
     * Invalid grade and course data are rejected explicitly.
     */
    public function test_invalid_input_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        learning_recommendations::recommend([
            $this->course(101.0, [
                $this->module('Invalid grade', COMPLETION_INCOMPLETE),
            ]),
        ]);
    }

    /**
     * Unknown non-null completion states are rejected rather than treated as complete or incomplete.
     */
    public function test_unknown_completion_state_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        learning_recommendations::recommend([
            $this->course(30.0, [
                $this->module('Invalid completion', 99),
            ]),
        ]);
    }

    /**
     * Several courses sort by performance priority and use course grades when activity grades are absent.
     */
    public function test_multiple_courses_are_prioritized_by_visible_performance(): void {
        $recommendations = learning_recommendations::recommend([
            [
                'courseid' => 2,
                'course_name' => 'Course B',
                'grade_percent' => 84.0,
                'course_completed' => false,
                'modules' => [
                    $this->module('Next topic', COMPLETION_INCOMPLETE),
                ],
            ],
            [
                'courseid' => 1,
                'course_name' => 'Course A',
                'grade_percent' => 51.0,
                'course_completed' => false,
                'modules' => [
                    $this->module('Review topic', COMPLETION_INCOMPLETE),
                ],
            ],
            [
                'courseid' => 3,
                'course_name' => 'Course C',
                'grade_percent' => 68.0,
                'course_completed' => false,
                'modules' => [
                    $this->module('Developing topic', COMPLETION_INCOMPLETE),
                ],
            ],
        ]);

        $this->assertSame(['Course A', 'Course C', 'Course B'], array_column($recommendations, 'course_name'));
        $this->assertSame([100, 75, 50], array_column($recommendations, 'priority'));
        $this->assertSame('course', $recommendations[0]['grade_source']);
        $this->assertSame(51.0, $recommendations[0]['grade_percent']);
    }

    /**
     * Make course data for the rule engine.
     *
     * @param float|null $grade Course grade percentage.
     * @param array $modules Activity records.
     * @return array
     */
    private function course(?float $grade, array $modules): array {
        return [
            'courseid' => 1,
            'course_name' => 'Course A',
            'grade_percent' => $grade,
            'course_completed' => false,
            'modules' => $modules,
        ];
    }

    /**
     * Make activity data for the rule engine.
     *
     * @param string $name Activity name.
     * @param int|null $completionstate Activity completion state.
     * @param float|null $grade Activity grade percentage.
     * @return array
     */
    private function module(string $name, ?int $completionstate, ?float $grade = null): array {
        return [
            'id' => 1,
            'name' => $name,
            'url' => null,
            'completion_state' => $completionstate,
            'grade_percent' => $grade,
        ];
    }
}
