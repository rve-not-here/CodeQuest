<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Controllers/DashboardController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\DashboardController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-566006c8ed4c05a96fcf7eafbff7bef681b58bf014523b27475d07eabe6d0434',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\DashboardController',
        'filename' => '/home/notdotguy/codequest/app/Http/Controllers/DashboardController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers',
    'name' => 'App\\Http\\Controllers\\DashboardController',
    'shortName' => 'DashboardController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 104,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'PRIORITY_TYPES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'name' => 'PRIORITY_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\\App\\Services\\NotificationService::TYPE_ASSESSMENT_UNLOCKED, \\App\\Services\\NotificationService::TYPE_LEARNING_REMINDER, \\App\\Services\\NotificationService::TYPE_DRAFT_REMINDER, \\App\\Services\\NotificationService::TYPE_TEACHER_ATTENTION, \\App\\Services\\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT]',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 32,
            'startTokenPos' => 67,
            'startFilePos' => 895,
            'endTokenPos' => 94,
            'endFilePos' => 1167,
          ),
        ),
        'docComment' => '/**
 * The only types the dashboard teaser may surface (US-810, §29). These are
 * the "act on this or read a broadcast" notifications: an unlocked-but-
 * unattempted assessment, a learning/draft reminder, a teacher that needs
 * attention, and an admin announcement. Instantaneous event feedback
 * (mission_completed, pass/fail, course and achievement events) is excluded
 * so the panel never becomes a ticker that competes with Continue Learning.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'dashboard' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
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
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resume' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'name' => 'resume',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\ResumeService',
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
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reminders' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'name' => 'reminders',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\StudentReminderService',
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
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'notifications' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'resume' => 
          array (
            'name' => 'resume',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\ResumeService',
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
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reminders' => 
          array (
            'name' => 'reminders',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\StudentReminderService',
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
            'endColumn' => 58,
            'parameterIndex' => 2,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 9,
            'endColumn' => 59,
            'parameterIndex' => 3,
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
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'currentClassName' => 'App\\Http\\Controllers\\DashboardController',
        'aliasName' => NULL,
      ),
      '__invoke' => 
      array (
        'name' => '__invoke',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 30,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'currentClassName' => 'App\\Http\\Controllers\\DashboardController',
        'aliasName' => NULL,
      ),
      'priorityNotifications' => 
      array (
        'name' => 'priorityNotifications',
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
            'startLine' => 96,
            'endLine' => 96,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The newest priority transmissions from the caller\'s own feed, capped at
 * two (US-810, §29). forUser() already orders newest-first and is scoped by
 * user_id, so the filter + slice keeps the two newest priority rows and
 * can never reach another user\'s.
 *
 * @return array<int, Notification>
 */',
        'startLine' => 96,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\DashboardController',
        'implementingClassName' => 'App\\Http\\Controllers\\DashboardController',
        'currentClassName' => 'App\\Http\\Controllers\\DashboardController',
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