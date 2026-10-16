# Week 7 Day 5: Moodle Assessment Engine Study

## Scope

This study follows the quiz and question-engine code in this Moodle checkout. Moodle separates the quiz activity workflow from the reusable question engine, which lets activities other than Quiz use question attempts as well.

## Main components

### Quiz activity (`mod_quiz`)

`mod_quiz` owns quiz settings, access rules, pages/sections, attempt records, and quiz-level grading. `quiz_settings` provides the activity context and creates `quiz_attempt` objects. The quiz structure refers to question slots, allowing the activity to control ordering, paging, marks, and sections independently from the underlying question definitions.

Starting an attempt goes through `startattempt.php`: Moodle requires login and a valid session key, checks quiz access and preflight rules, and prepares/starts an attempt before redirecting to the attempt page. Response submissions go through `processattempt.php`, which checks that the attempt belongs to the signed-in user, checks the attempt capability and page sequence, and delegates response processing to `quiz_attempt::process_attempt()`.

### Question bank and question types

The question bank stores reusable question definitions in categories associated with Moodle contexts. A quiz slot uses a selected question (or a random-selection rule) rather than copying its answer-processing logic into `mod_quiz`. The question bank plugins under `question/bank/` provide bank operations such as editing and managing categories. Question-type plugins under `question/type/` provide the behavior specific to a type; `question_type` is the shared base API, while individual plugins supply the question definition, response data, rendering, and grading rules.

### Question engine

`question_engine` is the persistence and factory API. It creates, loads, saves, and deletes a `question_usage_by_activity` (QUBA). The usage records its owning component and context and contains numbered slots, each backed by a `question_attempt`. A slot is not the question's database ID: it identifies this occurrence within this particular activity attempt, so a question can occur more than once.

Each `question_attempt` keeps the response/marking steps for one question. A question behavior (for example, deferred feedback or interactive behavior) controls how responses are accepted and when grading happens. Question-type code supplies the type-specific semantics; behavior and the question engine coordinate the attempt steps and state changes. Saving a quiz's QUBA persists those question-level details separately from the quiz's `quiz_attempts` record.

## State model

There are two related but distinct state machines:

- **Quiz attempt state:** `quiz_attempt` defines `notstarted`, `inprogress`, `overdue`, `submitted`, `finished`, and `abandoned`. Starting and processing endpoints move the quiz-level attempt through these statuses. A normal submission may finish immediately; time limits, grace periods, overdue handling, or pending manual grading can take a different path. `process_attempt()` validates timing, processes submitted actions, and determines the resulting status.
- **Per-question state:** `question_state` includes states such as `notstarted`, `todo`, `complete`, `needsgrading`, `gaveup`, and graded outcomes (`gradedright`, `gradedpartial`, `gradedwrong`), with manually graded variants. These describe a single question attempt, not the whole quiz. A response can be complete at the question level while the overall quiz attempt is still in progress.

The distinction matters when debugging assessment behavior: quiz access/submission rules belong to `mod_quiz`; response interpretation and per-question grading are handled by the question type, behavior, and question engine.

## Source trail

- [Quiz attempt start flow](../public/mod/quiz/startattempt.php)
- [Quiz response submission flow](../public/mod/quiz/processattempt.php)
- [Quiz attempt model and statuses](../public/mod/quiz/classes/quiz_attempt.php)
- [Quiz settings and attempt creation](../public/mod/quiz/classes/quiz_settings.php)
- [Question engine factory and persistence API](../public/question/engine/lib.php)
- [Question usage by activity](../public/question/engine/questionusage.php)
- [Per-question attempt model](../public/question/engine/questionattempt.php)
- [Question state definitions](../public/question/engine/states.php)
- [Question-type base API](../public/question/type/questiontypebase.php)
- [Question-bank plugins](../public/question/bank/)
- [Question-type plugins](../public/question/type/)
