<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/TeacherDashboardService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\TeacherDashboardService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-601eb46bffa7e357cbf2c6c5c5b169529fb16588ffd7f2c5750119dbe591d817',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\TeacherDashboardService',
        'filename' => '/home/notdotguy/codequest/app/Services/TeacherDashboardService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\TeacherDashboardService',
    'shortName' => 'TeacherDashboardService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The teacher dashboard (US-609, §5.0/§52) is the composition story of the
 * phase, not a new reporting engine. It lands on the established teacher
 * landing page (/students, where AuthController::homeFor sends teachers after
 * login) and composes that roster from the same services the detail pages
 * already use:
 *
 *   - the roster itself            StudentService::index
 *   - the fleet activity strip     TimelineService::feed (same beat vocabulary
 *                                  as the /activity page)
 *   - the per-course assessment
 *     summary                      CourseAnalyticsService::overview
 *   - the needs-attention KPI      AttentionService::list — deliberately the
 *                                  SAME computation as /needs-attention, so the
 *                                  headline number and that page can never
 *                                  disagree with each other
 *
 * Only four aggregate counts are genuinely new (total students, active
 * students, courses with students mid-course, and assessments passed across
 * the fleet), and each is a single bounded query — no per-student fan-out.
 *
 * countActiveStudents() and countPassedAssessments() are PUBLIC because the
 * admin console (US-702, AdminDashboardService) reuses them for its learning
 * and assessment panels — the active-window UNION and the passed-attempt count
 * must have exactly one home so the teacher and admin pages can never disagree.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 37,
    'endLine' => 168,
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
      'ACTIVE_WINDOW_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'name' => 'ACTIVE_WINDOW_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\App\\Services\\TimelineService::DEFAULT_WINDOW_DAYS',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 50,
            'startFilePos' => 2256,
            'endTokenPos' => 52,
            'endFilePos' => 2291,
          ),
        ),
        'docComment' => '/**
 * A student is "active" when they have at least one learning event in the
 * last ACTIVE_WINDOW_DAYS. The window reuses TimelineService\'s default
 * feed window on purpose, so "active" means the same span the Learning
 * Activity strip renders below it. A learning event is one of the four
 * timeline sources — a mission completion, an assessment attempt, an
 * activity row, or an xp_transaction — with login/logout bookkeeping
 * excluded (TimelineService::NON_LEARNING_ACTIVITY_TYPES).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 75,
      ),
    ),
    'immediateProperties' => 
    array (
      'analytics' => 
      array (
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
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
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 9,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'attention' => 
      array (
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'name' => 'attention',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AttentionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timeline' => 
      array (
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'name' => 'timeline',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\TimelineService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 9,
        'endColumn' => 50,
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 9,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attention' => 
          array (
            'name' => 'attention',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AttentionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'timeline' => 
          array (
            'name' => 'timeline',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\TimelineService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 9,
            'endColumn' => 50,
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
        'startLine' => 50,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'overview' => 
      array (
        'name' => 'overview',
        'parameters' => 
        array (
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '8',
              'attributes' => 
              array (
                'startLine' => 78,
                'endLine' => 78,
                'startTokenPos' => 108,
                'startFilePos' => 3436,
                'endTokenPos' => 108,
                'endFilePos' => 3436,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 30,
            'endColumn' => 43,
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
 * @param  int  $limit  number of recent fleet beats to surface
 * @return array{
 *     metrics: array{
 *         total_students: int,
 *         active_students: int,
 *         courses_in_progress: int,
 *         assessments_passed: int,
 *         needs_attention: int,
 *     },
 *     recent_activity: Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>,
 *     assessment_summary: Collection<int, array{
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
        'startLine' => 78,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'countStudents' => 
      array (
        'name' => 'countStudents',
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
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'coursesInProgress' => 
      array (
        'name' => 'coursesInProgress',
        'parameters' => 
        array (
          'summary' => 
          array (
            'name' => 'summary',
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 40,
            'endColumn' => 58,
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
 * @param  Collection<int, array{
 *     course: Course,
 *     fleet: int,
 *     engaged: int,
 *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
 *     avg_completion: int|null,
 *     pass_rate: int|null,
 *     distribution: array<int, int>,
 * }>  $summary
 */',
        'startLine' => 111,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'countPassedAssessments' => 
      array (
        'name' => 'countPassedAssessments',
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
        'startLine' => 118,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'countActiveStudents' => 
      array (
        'name' => 'countActiveStudents',
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
        'startLine' => 125,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
        'aliasName' => NULL,
      ),
      'recentActivity' => 
      array (
        'name' => 'recentActivity',
        'parameters' => 
        array (
          'limit' => 
          array (
            'name' => 'limit',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 37,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>
 */',
        'startLine' => 160,
        'endLine' => 167,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\TeacherDashboardService',
        'implementingClassName' => 'App\\Services\\TeacherDashboardService',
        'currentClassName' => 'App\\Services\\TeacherDashboardService',
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