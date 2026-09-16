<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AttentionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AttentionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-fada0cea95bf19ae02c9ce2ef9d08a217f5c322b1140a90ef224de04cf22ccb9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AttentionService',
        'filename' => '/home/notdotguy/codequest/app/Services/AttentionService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AttentionService',
    'shortName' => 'AttentionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Students Needing Attention (US-608, §19.0-§21.0).
 *
 * Attention signals are deterministic, documented, and explainable, and are
 * deliberately NOT an academic grade. Every signal is a binary rule with a
 * named threshold and an evidence string. A student is listed when ANY signal
 * fires: an OR union, never a weighted composite. A weighted score would read
 * as a grade to a teacher, which §21.0 forbids, and every threshold here
 * anchors to the product\'s own rubric (retry affordance, mission length,
 * passing_score); there is no intervention-outcome data anywhere to calibrate
 * weights against.
 *
 * Everything is derived from fleet-level aggregations over the progress,
 * attempt, and assessment tables, never per-student service fan-out, matching
 * CourseAnalyticsService (US-607). The per-student "current course" the
 * signals key off is derived in memory from the passed-course set and matches
 * DashboardService::currentCourse exactly; AttentionTest re-derives every
 * fixture student\'s signals from the student-facing services and asserts the
 * two agree (the same no-duplicate-formula defense as US-607).
 *
 * Days are counted at calendar-day granularity: an event fires its signal
 * when its day is at least the threshold\'s number of days before today
 * (start-of-day comparison), so the boundary is exact and free of sub-second
 * drift.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 390,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SIGNAL_PRIORITY' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'SIGNAL_PRIORITY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'boss_fail\', \'repeat_fail\', \'low_performance\', \'stalled\', \'never_started\', \'inactive\']',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 54,
            'startTokenPos' => 65,
            'startFilePos' => 1990,
            'endTokenPos' => 85,
            'endFilePos' => 2131,
          ),
        ),
        'docComment' => '/**
 * Primary-reason priority, highest first. boss_fail is the current-course
 * gate state, so it leads; plain inactivity ranks below never_started
 * because an untouched account is a clearer outreach case than a student
 * who engaged once and stopped.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'REPEAT_FAIL_THRESHOLD' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'REPEAT_FAIL_THRESHOLD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 98,
            'startFilePos' => 2454,
            'endTokenPos' => 98,
            'endFilePos' => 2454,
          ),
        ),
        'docComment' => '/**
 * Repeated-failed-challenges threshold. Two, not three: one failure is a
 * single data point and a retry is a designed affordance (US-409); a
 * second, separate failure is the smallest evidence the first attempt\'s
 * feedback did not convert.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'LOW_PERFORMANCE_MIN_ATTEMPTS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'LOW_PERFORMANCE_MIN_ATTEMPTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 111,
            'startFilePos' => 2650,
            'endTokenPos' => 111,
            'endFilePos' => 2650,
          ),
        ),
        'docComment' => '/**
 * Minimum scored attempts before low performance means anything. One
 * scored attempt is a data point, not a pattern.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
      'LOW_PERFORMANCE_PERCENT_OF_PASSING' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'LOW_PERFORMANCE_PERCENT_OF_PASSING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '60',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 124,
            'startFilePos' => 2926,
            'endTokenPos' => 124,
            'endFilePos' => 2927,
          ),
        ),
        'docComment' => '/**
 * Low-performance bar as a percent of the assessment\'s passing_score.
 * Averaging below 60% of the pass bar is scoring far beneath what the
 * challenge expects, not a barely-missed pass.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
      'STALL_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'STALL_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 137,
            'startFilePos' => 3316,
            'endTokenPos' => 137,
            'endFilePos' => 3317,
          ),
        ),
        'docComment' => '/**
 * Stall cut: no new mission completion on the current course for this
 * many days. Missions take minutes, so two full weeks without one more
 * completion on a course already started is halted work. Deliberately
 * shorter than INACTIVITY_DAYS so "dropped a course" and "gone quiet
 * entirely" stay distinguishable.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'INACTIVITY_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'INACTIVITY_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '21',
          'attributes' => 
          array (
            'startLine' => 91,
            'endLine' => 91,
            'startTokenPos' => 150,
            'startFilePos' => 3584,
            'endTokenPos' => 150,
            'endFilePos' => 3585,
          ),
        ),
        'docComment' => '/**
 * Inactivity cut: no engine event from any source for this many days.
 * Three weeks spans a two-week cycle plus a week; quiet that long is not
 * a short break. Deliberately longer than STALL_DAYS.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'NEVER_STARTED_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'NEVER_STARTED_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 98,
            'endLine' => 98,
            'startTokenPos' => 163,
            'startFilePos' => 3813,
            'endTokenPos' => 163,
            'endFilePos' => 3814,
          ),
        ),
        'docComment' => '/**
 * Never-started cut: account older than this with zero progress rows
 * anywhere. Two weeks is the grace for a new student to finish their
 * first mission.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'SIGNAL_LABELS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'name' => 'SIGNAL_LABELS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'boss_fail\' => \'BOSS CHALLENGE FAILED\', \'repeat_fail\' => \'REPEATED FAILURES\', \'low_performance\' => \'LOW PERFORMANCE\', \'stalled\' => \'STALLED\', \'never_started\' => \'NOT STARTED\', \'inactive\' => \'INACTIVE\']',
          'attributes' => 
          array (
            'startLine' => 105,
            'endLine' => 112,
            'startTokenPos' => 176,
            'startFilePos' => 3984,
            'endTokenPos' => 220,
            'endFilePos' => 4240,
          ),
        ),
        'docComment' => '/**
 * Display labels, keyed by signal. Indexes align with SIGNAL_PRIORITY.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'list' => 
      array (
        'name' => 'list',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<int, array{
 *     student: User,
 *     current_course: Course,
 *     signals: non-empty-array<int, string>,
 *     primary: string,
 *     reasons: array{}|array{boss_fail?: string, repeat_fail?: string, low_performance?: string, stalled?: string, inactive?: string, never_started?: string},
 *     last_activity_at: Carbon|null,
 * }>
 */',
        'startLine' => 124,
        'endLine' => 298,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
      'currentCourse' => 
      array (
        'name' => 'currentCourse',
        'parameters' => 
        array (
          'courses' => 
          array (
            'name' => 'courses',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 36,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'missionCounts' => 
          array (
            'name' => 'missionCounts',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 57,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'passedSet' => 
          array (
            'name' => 'passedSet',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 84,
            'endColumn' => 104,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'App\\Models\\Course',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The first active course, in progression order, with at least one
 * mission the student has not passed. Matches DashboardService::currentCourse:
 * completion reads the passed-attempt history, a zero-mission course is
 * skipped, and locked/draft courses are already excluded by $courses.
 *
 * @param  Collection<int, Course>  $courses
 * @param  Collection<int, int>  $missionCounts
 * @param  Collection<int, int>  $passedSet
 */',
        'startLine' => 310,
        'endLine' => 319,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
      'lastEventsByUser' => 
      array (
        'name' => 'lastEventsByUser',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, Collection<int, int|string>>
 */',
        'startLine' => 324,
        'endLine' => 344,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
      'lastEventAt' => 
      array (
        'name' => 'lastEventAt',
        'parameters' => 
        array (
          'sources' => 
          array (
            'name' => 'sources',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 50,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Illuminate\\Support\\Carbon',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, Collection<int, int|string>>  $sources
 */',
        'startLine' => 349,
        'endLine' => 368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
      'daysSince' => 
      array (
        'name' => 'daysSince',
        'parameters' => 
        array (
          'at' => 
          array (
            'name' => 'at',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Carbon\\CarbonInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 376,
            'endLine' => 376,
            'startColumn' => 32,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whole calendar days between a timestamp\'s day and today\'s day. Both
 * sides are snapped to start-of-day, so this is exact and repeatable no
 * matter what time of day the comparison happens. Carbon\'s diffInDays is
 * signed in Carbon 3, so the absolute value is taken.
 */',
        'startLine' => 376,
        'endLine' => 379,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
      'priorityOf' => 
      array (
        'name' => 'priorityOf',
        'parameters' => 
        array (
          'signal' => 
          array (
            'name' => 'signal',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * 0-based priority of a signal key; absent keys sort last.
 */',
        'startLine' => 384,
        'endLine' => 389,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionService',
        'implementingClassName' => 'App\\Services\\AttentionService',
        'currentClassName' => 'App\\Services\\AttentionService',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));