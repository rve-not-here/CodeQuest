<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AttentionNotificationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AttentionNotificationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-ef03a3f406a558afd41cff620ba47fe9e1a383caaabe4506f7ccd6cdca30bbf9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AttentionNotificationService',
        'filename' => '/home/notdotguy/codequest/app/Services/AttentionNotificationService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AttentionNotificationService',
    'shortName' => 'AttentionNotificationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Teacher attention notifications (US-806, §22.0-§25.0).
 *
 * A frequency-governed recurring notification, not a one-shot event: each
 * teacher_attention row targets its recipient\'s needs-attention inbox, is
 * scoped by ownership exactly like every other notification (a student never
 * receives one — the rows are keyed to the requesting teacher), and is
 * emitted lazily when the teacher opens the dashboard (/students).
 *
 * Emission is change-driven: the ordered attention signal set is stored with
 * each row (data.signals) and a new row is written only when the current set
 * differs from the teacher\'s latest stored set for that student. This is
 * independently throttled by a 24-hour cooldown, so a student who genuinely
 * changes state inside the window is suppressed now but stays pending (the
 * latest row still carries the previous set) and emits once the cooldown
 * lifts — delayed, never lost.
 *
 * The signal vocabulary is AttentionService::list() verbatim — never a second
 * attention rule set — and the title/message carry only what the
 * needs-attention page already exposes to teachers (username, signal label,
 * evidence reason). Nothing about the student is visible inside the student\'s
 * own center.
 *
 * Hook cost: /students renders the dashboard through
 * TeacherDashboardService::overview, which counts the same list, and then
 * syncs — AttentionService::list() runs twice per teacher dashboard request.
 * That is the tracked, acknowledged price of reusing the single source of
 * attention truth.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 165,
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
      'COOLDOWN_HOURS' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
        'name' => 'COOLDOWN_HOURS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '24',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 50,
            'startFilePos' => 2042,
            'endTokenPos' => 50,
            'endFilePos' => 2043,
          ),
        ),
        'docComment' => '/**
 * A fresh teacher_attention row is written at most once per 24 hours per
 * student, regardless of how the state changed. A genuine change inside
 * the window is delayed until the cooldown clears, not lost.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'attention' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
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
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'notifications' => 
      array (
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
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
        'startLine' => 51,
        'endLine' => 51,
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
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 9,
            'endColumn' => 59,
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
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
        'currentClassName' => 'App\\Services\\AttentionNotificationService',
        'aliasName' => NULL,
      ),
      'syncFor' => 
      array (
        'name' => 'syncFor',
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 29,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sync the requesting teacher\'s notification center with the current
 * needs-attention list: write a teacher_attention row for every student
 * whose ordered signal set differs from the teacher\'s latest stored set,
 * subject to the 24-hour cooldown. The writes run inside one transaction;
 * returns the number of rows created.
 */',
        'startLine' => 61,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
        'currentClassName' => 'App\\Services\\AttentionNotificationService',
        'aliasName' => NULL,
      ),
      'createFor' => 
      array (
        'name' => 'createFor',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 47,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'signals' => 
          array (
            'name' => 'signals',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 59,
            'endColumn' => 72,
            'parameterIndex' => 2,
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
 * @param  array{
 *     student: User,
 *     current_course: Course,
 *     signals: non-empty-array<int, string>,
 *     primary: string,
 *     reasons: array<string, string>,
 *     last_activity_at: CarbonInterface|null,
 * }  $row
 * @param  non-empty-array<int, string>  $signals
 */',
        'startLine' => 110,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
        'currentClassName' => 'App\\Services\\AttentionNotificationService',
        'aliasName' => NULL,
      ),
      'latestAttentionByStudent' => 
      array (
        'name' => 'latestAttentionByStudent',
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 47,
            'endColumn' => 59,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The teacher\'s newest teacher_attention row per student, keyed by
 * student id. One bounded query for the requesting user — no per-student
 * fan-out, matching the fleet-pages discipline (US-602/608).
 *
 * @return array<int, Notification>
 */',
        'startLine' => 137,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AttentionNotificationService',
        'implementingClassName' => 'App\\Services\\AttentionNotificationService',
        'currentClassName' => 'App\\Services\\AttentionNotificationService',
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