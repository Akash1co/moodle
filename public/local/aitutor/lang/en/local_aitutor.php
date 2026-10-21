<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

$string['pluginname'] = 'AI Tutor';
$string['groqapikey'] = 'Groq API key';
$string['groqapikey_desc'] = 'API key used for the primary Groq AI provider.';
$string['openrouterapikey'] = 'OpenRouter API key';
$string['openrouterapikey_desc'] = 'API key used when Groq is unavailable.';
$string['missingapikey'] = 'Configure at least one AI provider API key in the AI Tutor settings.';
$string['missinggroqapikey'] = 'Configure a Groq API key in the AI Tutor settings to generate quiz questions.';
$string['requestfailed'] = 'The AI provider request failed.';
$string['invalidquizinput'] = 'Enter course content before generating questions.';
$string['invalidquizresponse'] = 'The AI provider returned invalid quiz question data.';
$string['invalidquizcategory'] = 'Choose a question category belonging to the specified course.';
$string['recommendations'] = 'Learning recommendations';
$string['recommendationsheading'] = 'Your recommended next steps';
$string['recommendationlistheading'] = 'Recommended activities';
$string['performanceheading'] = 'Your course performance and completion';
$string['completionnotenabled'] = 'Completion tracking is not enabled for this course.';
$string['coursecompleted'] = 'Course completed.';
$string['notrackedactivities'] = 'No visible activities have completion tracking enabled.';
$string['completionprogress'] = '{$a->completed} of {$a->tracked} tracked activities completed';
$string['completionnotavailable'] = 'Completion tracking is not enabled for your enrolled courses, so incomplete activities cannot be identified yet.';
$string['noenrolledcourses'] = 'You are not enrolled in any active courses with learning recommendations.';
$string['norecommendations'] = 'No incomplete, visible activities with completion tracking were found.';
$string['noperformancecontext'] = 'No visible grade is available for this course or activity.';
$string['performancecontext'] = 'Recommendation uses a visible {$a->source} grade of {$a->grade}.';
$string['gradesourceactivity'] = 'activity';
$string['gradesourcecourse'] = 'course total';
$string['prioritylabel'] = 'Priority';
$string['priorityhigh'] = 'High';
$string['prioritymedium'] = 'Medium';
$string['prioritynext'] = 'Next step';
$string['reasonnograde'] = 'This activity is incomplete. No visible grade is available, so it is suggested as a next step.';
$string['reasonlowactivity'] = 'Your score on this activity was {$a}%, below 60%. Review it to reinforce this topic.';
$string['reasonlowcourse'] = 'Your visible course grade is {$a}%, below 60%. This unfinished activity is a suggested review step.';
$string['reasondevelopingactivity'] = 'Your score on this activity was {$a}%. Reviewing this incomplete topic is a useful next step.';
$string['reasondevelopingcourse'] = 'Your visible course grade is {$a}%. This unfinished activity is a useful next step.';
$string['reasonstrongactivity'] = 'Your score on this activity was {$a}%. This is the next incomplete activity in the course.';
$string['reasonstrongcourse'] = 'Your visible course grade is {$a}%. This is the next incomplete activity in the course.';
$string['openactivity'] = 'Open activity';