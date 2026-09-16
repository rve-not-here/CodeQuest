<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/ResumeService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\ResumeService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-c27bc1b65237a547834ec2d91e922a4d49a073793124ccccae243a08e52007ab',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\ResumeService',
        'filename' => '/home/notdotguy/codequest/app/Services/ResumeService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\ResumeService',
    'shortName' => 'ResumeService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves where "Continue Learning" takes the student (US-504).
 *
 * §14.0 priority, strictest useful scope first:
 *   1. unfinished lesson/challenge -> the first unfinished mission in the
 *      current course, in course order, drafts preferred (exactly what
 *      DashboardService::nextMission() derives, the same traversal the
 *      learning path uses). In-progress work is never skipped for a later,
 *      untouched mission.
 *   2. current section -> a mission exists iff some section still has
 *      unfinished work, so nextMission() already lands within the right
 *      section; that section rides along as the position\'s context.
 *   3. current course -> every mission is complete but the Boss Challenge is
 *      still outstanding (the US-410/US-414 edge). The student resumes at
 *      course level. The destination is the learning surface, NEVER the
 *      assessment.
 *
 * Guarantees (never): a random challenge (always the derived position), a
 * jump straight to an assessment (course-level resume routes to the learning
 * path, not assessment.show), or ignoring incomplete work (the first
 * unfinished mission wins).
 *
 * Returns null only when every course has had its Boss Challenge passed,
 * which drives the dashboard all-clear state.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 73,
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
        'declaringClassName' => 'App\\Services\\ResumeService',
        'implementingClassName' => 'App\\Services\\ResumeService',
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
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 9,
        'endColumn' => 52,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ResumeService',
        'implementingClassName' => 'App\\Services\\ResumeService',
        'currentClassName' => 'App\\Services\\ResumeService',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
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
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 29,
            'endColumn' => 38,
            'parameterIndex' => 0,
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
                  'name' => 'array',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return null|array{
 *     type: \'mission\'|\'course\',
 *     course: Course,
 *     section?: Section|null,
 *     mission?: Mission,
 * }
 */',
        'startLine' => 49,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\ResumeService',
        'implementingClassName' => 'App\\Services\\ResumeService',
        'currentClassName' => 'App\\Services\\ResumeService',
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