<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/CourseAnalyticsService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\CourseAnalyticsService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-97352867a30f000d8f6eb8f2336188858d23a6a212cacea09b154f9468dc5e3d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\CourseAnalyticsService',
        'filename' => '/home/notdotguy/codequest/app/Services/CourseAnalyticsService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\CourseAnalyticsService',
    'shortName' => 'CourseAnalyticsService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Per-course aggregate analytics for the teacher area (US-607, §24.0-§26.0).
 *
 * Every metric is calculated from real records in SQL-level aggregation queries
 * (GROUP BY over the progress and assessment-attempt tables), never by
 * iterating the roster through the per-student services. The state vocabulary
 * still matches the student-facing services exactly: COMPLETED is
 * hasPassed() (a history \'passed\' attempt), READY is isUnlocked() (all
 * missions done AND an active assessment, so a student who finished a course
 * whose challenge is not open stays in-progress), and engagement is any
 * Progress row on the course. CourseAnalyticsTest locks that equivalence by
 * comparing every fixture student\'s bucket against the per-student services.
 *
 * The bucket counts partition the student fleet per course, so NOT-STARTED is
 * the fleet minus the engaged, and the four counts always sum to the fleet
 * size. Courses render in order_num; locked/draft courses and courses with
 * zero missions are excluded (the same universe the student side renders).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 207,
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
      'DISTRIBUTION_BANDS' => 
      array (
        'declaringClassName' => 'App\\Services\\CourseAnalyticsService',
        'implementingClassName' => 'App\\Services\\CourseAnalyticsService',
        'name' => 'DISTRIBUTION_BANDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'0-24\', \'25-49\', \'50-74\', \'75-99\', \'100\']',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 55,
            'startFilePos' => 1822,
            'endTokenPos' => 69,
            'endFilePos' => 1863,
          ),
        ),
        'docComment' => '/**
 * Completion-percentage band labels for the distribution (§53.0). A
 * histogram is the one chart that genuinely adds information over the four
 * bucket counts: bucket counts hide whether in-progress students cluster
 * at the start, spread across the course, or sit just short of the
 * challenge. The distribution is keyed by band order (0..4), not by these
 * labels, because PHP coerces the numeric "100" label to an integer key.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 81,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'overview' => 
      array (
        'name' => 'overview',
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
 *     course: Course,
 *     fleet: int,
 *     engaged: int,
 *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *     avg_completion: int|null,
 *     pass_rate: int|null,
 *     distribution: array<int, int>,
 * }>
 */',
        'startLine' => 53,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseAnalyticsService',
        'implementingClassName' => 'App\\Services\\CourseAnalyticsService',
        'currentClassName' => 'App\\Services\\CourseAnalyticsService',
        'aliasName' => NULL,
      ),
      'course' => 
      array (
        'name' => 'course',
        'parameters' => 
        array (
          'course' => 
          array (
            'name' => 'course',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Course',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 9,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fleet' => 
          array (
            'name' => 'fleet',
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
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'total' => 
          array (
            'name' => 'total',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'progressRows' => 
          array (
            'name' => 'progressRows',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 9,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'attemptRows' => 
          array (
            'name' => 'attemptRows',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'assessmentStatus' => 
          array (
            'name' => 'assessmentStatus',
            'default' => NULL,
            'type' => 
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
                      'name' => 'string',
                      'isIdentifier' => true,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 9,
            'endColumn' => 33,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
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
 * @param  Collection<int, \\stdClass>  $progressRows
 * @param  Collection<int, \\stdClass>  $attemptRows
 * @return array{
 *     course: Course,
 *     fleet: int,
 *     engaged: int,
 *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *     avg_completion: int|null,
 *     pass_rate: int|null,
 *     distribution: array<int, int>,
 * }
 */',
        'startLine' => 114,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseAnalyticsService',
        'implementingClassName' => 'App\\Services\\CourseAnalyticsService',
        'currentClassName' => 'App\\Services\\CourseAnalyticsService',
        'aliasName' => NULL,
      ),
      'bandIndexFor' => 
      array (
        'name' => 'bandIndexFor',
        'parameters' => 
        array (
          'percent' => 
          array (
            'name' => 'percent',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 35,
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
 * Order index into DISTRIBUTION_BANDS for a completion percentage.
 */',
        'startLine' => 182,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseAnalyticsService',
        'implementingClassName' => 'App\\Services\\CourseAnalyticsService',
        'currentClassName' => 'App\\Services\\CourseAnalyticsService',
        'aliasName' => NULL,
      ),
      'fleetSize' => 
      array (
        'name' => 'fleetSize',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 203,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseAnalyticsService',
        'implementingClassName' => 'App\\Services\\CourseAnalyticsService',
        'currentClassName' => 'App\\Services\\CourseAnalyticsService',
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