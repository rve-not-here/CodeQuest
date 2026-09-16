<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/SystemStatusService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\SystemStatusService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-22f0f37957828386bd4e174bef5225f20f50b548592642ba73fe620a0ef306ac',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\SystemStatusService',
        'filename' => '/home/notdotguy/codequest/app/Services/SystemStatusService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\SystemStatusService',
    'shortName' => 'SystemStatusService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Operational/infrastructure status for /admin/system (US-711, §33.0). This
 * is infrastructure visibility, not data aggregation like the rest of Phase 7:
 *
 *   - database connectivity is a REAL connection attempt (getPdo + SELECT 1),
 *     never a read of the connection config
 *   - migrations status is a read-only peek at the migrations table
 *   - storage/log health is local filesystem state
 *
 * The secret boundary is structural, the same posture as sensitive model
 * fields: every config value the status may surface must pass through
 * safeConfig(), which refuses any key outside SAFE_CONFIG_KEYS. DB_PASSWORD,
 * APP_KEY, session secrets, API keys, and connection objects can never reach
 * this class\'s output; the connection-feature read also never echoes exception
 * text (DSN fragments can leak through PDO exception messages).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 154,
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
      'SAFE_CONFIG_KEYS' => 
      array (
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'name' => 'SAFE_CONFIG_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'app.name\', \'app.env\', \'app.debug\', \'logging.default\']',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 50,
            'startFilePos' => 1306,
            'endTokenPos' => 61,
            'endFilePos' => 1360,
          ),
        ),
        'docComment' => '/**
 * The only config keys this view is allowed to surface. Anything else
 * fails loud in safeConfig(); a future secret read would have to be
 * added here first, where the review is explicit.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 93,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'status' => 
      array (
        'name' => 'status',
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
 *     application: array{online: bool, name: string, environment: string, debug: bool, laravel_version: string, php_version: string},
 *     database: array{connected: bool, driver: string, server_version: string|null},
 *     migrations: array{readable: bool, applied: int, latest_batch: int},
 *     storage: array{log_channel: string, logs_writable: bool, log_file_exists: bool, log_file_last_modified: int|null, log_file_size: int|null},
 * }
 */',
        'startLine' => 44,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
        'aliasName' => NULL,
      ),
      'application' => 
      array (
        'name' => 'application',
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
 * @return array{online: bool, name: string, environment: string, debug: bool, laravel_version: string, php_version: string}
 */',
        'startLine' => 57,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
        'aliasName' => NULL,
      ),
      'database' => 
      array (
        'name' => 'database',
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
 * @return array{connected: bool, driver: string, server_version: string|null}
 */',
        'startLine' => 72,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
        'aliasName' => NULL,
      ),
      'migrations' => 
      array (
        'name' => 'migrations',
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
 * @return array{readable: bool, applied: int, latest_batch: int}
 */',
        'startLine' => 97,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
        'aliasName' => NULL,
      ),
      'storage' => 
      array (
        'name' => 'storage',
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
 * @return array{log_channel: string, logs_writable: bool, log_file_exists: bool, log_file_last_modified: int|null, log_file_size: int|null}
 */',
        'startLine' => 117,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
        'aliasName' => NULL,
      ),
      'safeConfig' => 
      array (
        'name' => 'safeConfig',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 33,
            'endColumn' => 43,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 146,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\SystemStatusService',
        'implementingClassName' => 'App\\Services\\SystemStatusService',
        'currentClassName' => 'App\\Services\\SystemStatusService',
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