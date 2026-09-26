<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CodeQuest JS Grader
    |--------------------------------------------------------------------------
    |
    | The internal service that runs hidden behavioral tests for coding
    | missions inside ephemeral sandboxes. Laravel never executes student
    | JavaScript itself and never controls the container runtime directly.
    |
    | When url is empty, missions carrying behavioral tests fail closed:
    | grading is reported unavailable and no completion or XP is awarded.
    | Missions without behavioral tests keep pure structural validation.
    |
    */

    'url' => env('CODEQUEST_GRADER_URL', ''),

    'token' => env('CODEQUEST_GRADER_TOKEN', ''),

    'timeout_ms' => (int) env('CODEQUEST_GRADER_TIMEOUT_MS', 15000),

    'max_source_bytes' => (int) env('CODEQUEST_GRADER_MAX_SOURCE_BYTES', 65536),

    'max_tests_per_mission' => (int) env('CODEQUEST_GRADER_MAX_TESTS', 20),

];
