<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AdminDashboardService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AdminDashboardService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-6c3e8b6b77c49f6a46c718f2a3153e0e089a42b42d9a2c2d219c7f61b0e5b233',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AdminDashboardService',
        'filename' => '/home/notdotguy/codequest/app/Services/AdminDashboardService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AdminDashboardService',
    'shortName' => 'AdminDashboardService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Admin console overview (US-702, §8.0). Not a second reporting engine — the
 * learning figures reuse the exact same aggregates the teacher area renders:
 *
 *   - active students           TeacherDashboardService::countActiveStudents
 *                               (the four-source learning-event UNION)
 *   - assessments passed        TeacherDashboardService::countPassedAssessments
 *   - course completions        sum of CourseAnalyticsService::overview()
 *                               bucket \'completed\' (distinct students who
 *                               passed each active course — the same universe
 *                               the analytics page renders)
 *
 * Only the account and catalog counts are new (total/active/inactive users,
 * courses/sections/challenges, attempts), and each is a single bounded query,
 * mirroring the teacher dashboard\'s no-fan-out discipline. "Recent system
 * activity" always comes from the dedicated AdminAuditService — never from
 * TimelineService, whose learning-beat vocabulary is a different concept.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 129,
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
    ),
    'immediateProperties' => 
    array (
      'teacher' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'name' => 'teacher',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\TeacherDashboardService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'analytics' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'name' => 'analytics',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\CourseAnalyticsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 9,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'audit' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'name' => 'audit',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AdminAuditService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'teacher' => 
          array (
            'name' => 'teacher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\TeacherDashboardService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'analytics' => 
          array (
            'name' => 'analytics',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\CourseAnalyticsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 9,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'audit' => 
          array (
            'name' => 'audit',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AdminAuditService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 9,
            'endColumn' => 49,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'currentClassName' => 'App\\Services\\AdminDashboardService',
        'aliasName' => NULL,
      ),
      'overview' => 
      array (
        'name' => 'overview',
        'parameters' => 
        array (
          'activityLimit' => 
          array (
            'name' => 'activityLimit',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 58,
                'endLine' => 58,
                'startTokenPos' => 108,
                'startFilePos' => 2149,
                'endTokenPos' => 108,
                'endFilePos' => 2150,
              ),
            ),
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 30,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * @return array{
 *     metrics: array{
 *         users_total: int,
 *         users_active: int,
 *         users_inactive: int,
 *         courses: int,
 *         sections: int,
 *         challenges: int,
 *         boss_challenges: int,
 *         attempts: int,
 *         passed_attempts: int,
 *         active_students: int,
 *         course_completions: int,
 *     },
 *     recent_system_activity: Collection<int, AdminAudit>,
 * }
 */',
        'startLine' => 58,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'currentClassName' => 'App\\Services\\AdminDashboardService',
        'aliasName' => NULL,
      ),
      'metrics' => 
      array (
        'name' => 'metrics',
        'parameters' => 
        array (
          'courseOverview' => 
          array (
            'name' => 'courseOverview',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 96,
                'endLine' => 96,
                'startTokenPos' => 165,
                'startFilePos' => 3478,
                'endTokenPos' => 165,
                'endFilePos' => 3481,
              ),
            ),
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
                      'name' => 'Illuminate\\Support\\Collection',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 29,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * Fleet-wide top-line metrics, reusable by the admin console and the
 * System Analytics drill-down (US-710). Pass an already-fetched
 * CourseAnalyticsService::overview() collection to avoid recomputing the
 * course aggregates twice on one page; the sum below stays the single
 * source of the course-completions figure either way.
 *
 * @param  Collection<int, array{
 *     course: Course,
 *     fleet: int,
 *     engaged: int,
 *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *     avg_completion: int|null,
 *     pass_rate: int|null,
 *     distribution: array<int, int>,
 * }>|null  $courseOverview
 * @return array{
 *     users_total: int,
 *     users_active: int,
 *     users_inactive: int,
 *     courses: int,
 *     sections: int,
 *     challenges: int,
 *     boss_challenges: int,
 *     attempts: int,
 *     passed_attempts: int,
 *     active_students: int,
 *     course_completions: int,
 * }
 */',
        'startLine' => 96,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'currentClassName' => 'App\\Services\\AdminDashboardService',
        'aliasName' => NULL,
      ),
      'courseCompletions' => 
      array (
        'name' => 'courseCompletions',
        'parameters' => 
        array (
          'courseOverview' => 
          array (
            'name' => 'courseOverview',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 124,
                'endLine' => 124,
                'startTokenPos' => 374,
                'startFilePos' => 4724,
                'endTokenPos' => 374,
                'endFilePos' => 4727,
              ),
            ),
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
                      'name' => 'Illuminate\\Support\\Collection',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 40,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * @param  Collection<int, array{
 *     course: Course,
 *     fleet: int,
 *     engaged: int,
 *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *     avg_completion: int|null,
 *     pass_rate: int|null,
 *     distribution: array<int, int>,
 * }>|null  $courseOverview
 */',
        'startLine' => 124,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminDashboardService',
        'implementingClassName' => 'App\\Services\\AdminDashboardService',
        'currentClassName' => 'App\\Services\\AdminDashboardService',
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