<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/SectionProgressService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\SectionProgressService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-043a3223027b6adb2dcdbfe5d5b26a1addde8d9dd903968523d6d5968fe1a6ee',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\SectionProgressService',
        'filename' => '/home/notdotguy/codequest/app/Services/SectionProgressService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\SectionProgressService',
    'shortName' => 'SectionProgressService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds the section-progress overview (US-503): each section\'s mission
 * progress and a server-derived state label, grouped under its course.
 *
 * Derivation reuses the Phase 3/4 traversal in LearningPathService::build()
 * (real course->section->mission hierarchy, per-mission Progress rows) — no
 * independent aggregation. A section\'s counts come from that section\'s own
 * missions; the flat "repeated course-level percentage" is never used. The
 * per-course counts are likewise whatever build() already derives.
 *
 * Section state labels:
 *   EMPTY       -> section has no missions (vacuously complete, contributes
 *                  nothing — never masks another section\'s real state)
 *   DONE        -> every mission in the section completed
 *   IN PROGRESS -> some (not all) missions completed
 *   NOT STARTED -> no missions completed yet
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 82,
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
      'path' => 
      array (
        'declaringClassName' => 'App\\Services\\SectionProgressService',
        'implementingClassName' => 'App\\Services\\SectionProgressService',
        'name' => 'path',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\LearningPathService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
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
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\LearningPathService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 9,
            'endColumn' => 50,
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
        'startLine' => 29,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SectionProgressService',
        'implementingClassName' => 'App\\Services\\SectionProgressService',
        'currentClassName' => 'App\\Services\\SectionProgressService',
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
            'startLine' => 44,
            'endLine' => 44,
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
 *     progress: array{completed: int, total: int, percent: int},
 *     sections: Collection<int, array{
 *         section: Section,
 *         progress: array{completed: int, total: int, percent: int},
 *         state: string,
 *     }>,
 * }>
 */',
        'startLine' => 44,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SectionProgressService',
        'implementingClassName' => 'App\\Services\\SectionProgressService',
        'currentClassName' => 'App\\Services\\SectionProgressService',
        'aliasName' => NULL,
      ),
      'stateFor' => 
      array (
        'name' => 'stateFor',
        'parameters' => 
        array (
          'progress' => 
          array (
            'name' => 'progress',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 31,
            'endColumn' => 45,
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
        'docComment' => '/**
 * @param  array{completed: int, total: int, percent: int}  $progress
 */',
        'startLine' => 66,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SectionProgressService',
        'implementingClassName' => 'App\\Services\\SectionProgressService',
        'currentClassName' => 'App\\Services\\SectionProgressService',
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