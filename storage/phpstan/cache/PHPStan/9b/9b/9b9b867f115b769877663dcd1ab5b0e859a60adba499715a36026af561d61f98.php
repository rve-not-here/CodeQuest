<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/CompetencyService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\CompetencyService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-96c548d0641c2f26019bdaee22f818298d709eb1bab4031fffe3f50d419def9e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\CompetencyService',
        'filename' => '/home/notdotguy/codequest/app/Services/CompetencyService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\CompetencyService',
    'shortName' => 'CompetencyService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds the competency overview (US-507): one competency per active course
 * with missions, all derived server-side from real learning and assessment
 * data. XP figures play no part in any competency value (§23.0); the only
 * wrong-submission signal reads the transaction log\'s penalty rows as a
 * per-mission engagement journal, and their amounts are never consulted.
 *
 * The small state model (§26.0), evaluated in order per course:
 *   NOT STARTED   -> no completed mission, no wrong submission, no attempt
 *   DEVELOPING    -> first mission completed or first wrong submission, with
 *                    fewer than half the course\'s missions done
 *   PRACTICING    -> at least half the missions done, or the Boss Challenge
 *                    attempted without a pass yet
 *   DEMONSTRATED  -> every mission completed and the Boss Challenge passed
 *
 * Monotonicity mirrors US-409/410: DEMONSTRATED reads hasPassed(), which is
 * attempt-history based, and Progress rows are never deleted, so a failed
 * retry after the first pass can never downgrade a competency.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 160,
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
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'name' => 'dashboard',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\DashboardService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'assessments' => 
      array (
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'name' => 'assessments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AssessmentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 55,
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
                'name' => 'App\\Services\\DashboardService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'assessments' => 
          array (
            'name' => 'assessments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AssessmentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'overview' => 
      array (
        'name' => 'overview',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 30,
            'endColumn' => 39,
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
 * @return Collection<int, array{
 *     course: Course,
 *     name: string,
 *     state: \'not_started\'|\'developing\'|\'practicing\'|\'demonstrated\',
 *     completedMissions: int,
 *     totalMissions: int,
 *     percent: int,
 *     wrongSubmissions: int,
 *     attempts: int,
 *     challengePassed: bool,
 * }>
 */',
        'startLine' => 50,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'summarise' => 
      array (
        'name' => 'summarise',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 1,
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
 * @return array{
 *     course: Course,
 *     name: string,
 *     state: \'not_started\'|\'developing\'|\'practicing\'|\'demonstrated\',
 *     completedMissions: int,
 *     totalMissions: int,
 *     percent: int,
 *     wrongSubmissions: int,
 *     attempts: int,
 *     challengePassed: bool,
 * }
 */',
        'startLine' => 75,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'stateFor' => 
      array (
        'name' => 'stateFor',
        'parameters' => 
        array (
          'done' => 
          array (
            'name' => 'done',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 31,
            'endColumn' => 39,
            'parameterIndex' => 0,
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 42,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attempts' => 
          array (
            'name' => 'attempts',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'wrongSubmissions' => 
          array (
            'name' => 'wrongSubmissions',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 69,
            'endColumn' => 89,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'passed' => 
          array (
            'name' => 'passed',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 92,
            'endColumn' => 103,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return \'not_started\'|\'developing\'|\'practicing\'|\'demonstrated\'
 */',
        'startLine' => 103,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'attemptCount' => 
      array (
        'name' => 'attemptCount',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
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
            'startColumn' => 35,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 47,
            'endColumn' => 60,
            'parameterIndex' => 1,
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
        'docComment' => NULL,
        'startLine' => 120,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'wrongSubmissionCount' => 
      array (
        'name' => 'wrongSubmissionCount',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 43,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'missionIds' => 
          array (
            'name' => 'missionIds',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 55,
            'endColumn' => 74,
            'parameterIndex' => 1,
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
 * Wrong submissions on the course\'s own missions. The only per-mission
 * record of a wrong submission is the penalty row in the transaction log;
 * it is queried strictly as an engagement journal (presence/count), never
 * for its amount.
 *
 * @param  iterable<int, mixed>  $missionIds
 */',
        'startLine' => 142,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
        'aliasName' => NULL,
      ),
      'nameFor' => 
      array (
        'name' => 'nameFor',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 30,
            'endColumn' => 41,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 151,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CompetencyService',
        'implementingClassName' => 'App\\Services\\CompetencyService',
        'currentClassName' => 'App\\Services\\CompetencyService',
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