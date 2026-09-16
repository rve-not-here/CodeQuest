<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AchievementService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AchievementService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-e3d88ac67ddef2a0859b23e5d7e5335db2e86dab082f63732359e9d986ec9275',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AchievementService',
        'filename' => '/home/notdotguy/codequest/app/Services/AchievementService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AchievementService',
    'shortName' => 'AchievementService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Server-authoritative achievement unlocks (US-508).
 *
 * An achievement is a catalog row (seeded system content) and an award is a
 * (user, achievement) pair in the award ledger, unique at the database level.
 * There is no client-suppliable path to an unlock under any name: award() is
 * reached only from the two server-side trigger points below, and the check
 * runs at the moment of the triggering event, inside the same transaction
 * that records the event, so a failed award write rolls the event back.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 258,
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
      'SLUG_FIRST_CHALLENGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'SLUG_FIRST_CHALLENGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'first_challenge\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 78,
            'startFilePos' => 925,
            'endTokenPos' => 78,
            'endFilePos' => 941,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'SLUG_FIRST_COURSE' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'SLUG_FIRST_COURSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'first_course\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 89,
            'startFilePos' => 982,
            'endTokenPos' => 89,
            'endFilePos' => 995,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'SLUG_STREAK_3' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'SLUG_STREAK_3',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'streak_3\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 100,
            'startFilePos' => 1032,
            'endTokenPos' => 100,
            'endFilePos' => 1041,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'SLUG_FULL_CLEAR' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'SLUG_FULL_CLEAR',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'full_clear\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 111,
            'startFilePos' => 1080,
            'endTokenPos' => 111,
            'endFilePos' => 1091,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'STREAK_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'STREAK_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 191,
            'startFilePos' => 1599,
            'endTokenPos' => 191,
            'endFilePos' => 1599,
          ),
        ),
        'docComment' => '/**
 * A learning day counts toward the streak only on the day it happens.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
    ),
    'immediateProperties' => 
    array (
      'notifications' => 
      array (
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'name' => 'notifications',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\NotificationService',
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
        'endColumn' => 59,
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
          'notifications' => 
          array (
            'name' => 'notifications',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\NotificationService',
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
            'endColumn' => 59,
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
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'slugs' => 
      array (
        'name' => 'slugs',
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
 * @return array<int, string>
 */',
        'startLine' => 44,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'evaluateMissionCompletion' => 
      array (
        'name' => 'evaluateMissionCompletion',
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
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 47,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks the mission-completion triggers. Called from inside the mission
 * completion transaction in MissionService, after the Progress row is
 * written, so this freshly completed mission is part of the counts read
 * here.
 */',
        'startLine' => 65,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'evaluateAssessmentPass' => 
      array (
        'name' => 'evaluateAssessmentPass',
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 44,
            'endColumn' => 53,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks the Boss Challenge pass triggers. Called from inside the
 * evaluateAttempt transaction, in the first-pass branch, after the
 * attempt row is saved as passed, so this pass is part of the history
 * read here.
 */',
        'startLine' => 86,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'currentStreak' => 
      array (
        'name' => 'currentStreak',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 35,
            'endColumn' => 44,
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
 * Consecutive calendar days on which the user completed at least one
 * mission, counted from the most recent completion day backwards.
 */',
        'startLine' => 107,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'award' => 
      array (
        'name' => 'award',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 27,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 39,
            'endColumn' => 50,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Grant an achievement, idempotently. Returns false when the user already
 * holds it; the database unique (user, achievement) constraint backs the
 * guard. Throws when the catalog has no row for the slug: that is a
 * seeding error, and it rolls back the event transaction with a clear
 * message rather than silently skipping.
 */',
        'startLine' => 143,
        'endLine' => 179,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'catalog' => 
      array (
        'name' => 'catalog',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 29,
            'endColumn' => 38,
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
 * The full catalog annotated with the user\'s award state. Display support
 * for the achievements page (still a standby shell); the award ledger
 * remains write-only through award().
 *
 * @return Collection<int, array{
 *     slug: string,
 *     name: string,
 *     description: ?string,
 *     awarded: bool,
 *     unlocked_at: ?Carbon,
 * }>
 */',
        'startLine' => 194,
        'endLine' => 215,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
        'aliasName' => NULL,
      ),
      'allActiveCoursesPassed' => 
      array (
        'name' => 'allActiveCoursesPassed',
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
            'startLine' => 224,
            'endLine' => 224,
            'startColumn' => 45,
            'endColumn' => 54,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * "Every active course\'s Boss Challenge passed" (§27, full_clear). The
 * eligible set is active courses that have at least one mission and an
 * assessment, mirroring the zero-mission/locked exclusion used by current
 * course resolution and competency: a course that can never be passed is
 * not part of the set, and an empty set never clears.
 */',
        'startLine' => 224,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AchievementService',
        'implementingClassName' => 'App\\Services\\AchievementService',
        'currentClassName' => 'App\\Services\\AchievementService',
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