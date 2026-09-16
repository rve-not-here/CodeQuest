<?php declare(strict_types = 1);

// osfsl-/home/notdotguy/codequest/app/Services/AdminAnalyticsService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AdminAnalyticsService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-e78bccdbc9434aae9bfa8200ead5ce4fdc07dc1a47c879a7dbc60532b593b94f-8.5.10-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AdminAnalyticsService',
        'filename' => '/home/notdotguy/codequest/app/Services/AdminAnalyticsService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AdminAnalyticsService',
    'shortName' => 'AdminAnalyticsService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * System Analytics drill-down (US-710, §29.0/§30.0). Reuses, never re-derives:
 * top-line system metrics come from AdminDashboardService::metrics(), the
 * per-course table is the same CourseAnalyticsService::overview() collection
 * the dashboard sums over, and the XP administration figures come from
 * XpService::fleetSummary(). The only new SQL here is the bounded fleet role
 * breakdown. Reading these rows writes nothing — the page has no mutations.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 99,
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
      'dashboard' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
        'name' => 'dashboard',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AdminDashboardService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
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
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xp' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
        'name' => 'xp',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\XpService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 38,
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
          'dashboard' => 
          array (
            'name' => 'dashboard',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AdminDashboardService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'xp' => 
          array (
            'name' => 'xp',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\XpService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 38,
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
        'startLine' => 19,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
        'currentClassName' => 'App\\Services\\AdminAnalyticsService',
        'aliasName' => NULL,
      ),
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array{
 *     system: array{
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
 *     roles: array<string, int>,
 *     xp: array{
 *         awarded: int,
 *         spent: int,
 *         deducted: int,
 *         outstanding: int,
 *         accounts: int,
 *         by_type: list<array{
 *             type: string,
 *             label: string,
 *             direction: \'award\'|\'spend\'|\'deduct\',
 *             entries: int,
 *             total: int,
 *         }>,
 *     },
 *     courses: Collection<int, array{
 *         course: Course,
 *         fleet: int,
 *         engaged: int,
 *         buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *         avg_completion: int|null,
 *         pass_rate: int|null,
 *         distribution: array<int, int>,
 *     }>,
 * }
 */',
        'startLine' => 66,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
        'currentClassName' => 'App\\Services\\AdminAnalyticsService',
        'aliasName' => NULL,
      ),
      'roleBreakdown' => 
      array (
        'name' => 'roleBreakdown',
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
 * Fleet accounts by role. Normalized to the full ROLES set so every key
 * is always present for the panel, zero for unpopulated roles.
 *
 * @return array<string, int>
 */',
        'startLine' => 84,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAnalyticsService',
        'implementingClassName' => 'App\\Services\\AdminAnalyticsService',
        'currentClassName' => 'App\\Services\\AdminAnalyticsService',
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