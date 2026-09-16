<?php declare(strict_types = 1);

// osfsl-/home/notdotguy/codequest/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Testing/RefreshDatabase.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Testing\RefreshDatabase
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-fc6eda5d215f45e9346b5677a6493b71b8c20affee8b03638d20e0e928d54077-8.5.10-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'filename' => '/home/notdotguy/codequest/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Testing/RefreshDatabase.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Testing',
    'name' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
    'shortName' => 'RefreshDatabase',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 200,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Foundation\\Testing\\Traits\\CanConfigureMigrationCommands',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'refreshDatabase' => 
      array (
        'name' => 'refreshDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define hooks to migrate the database before and after each test.
 *
 * @return void
 */',
        'startLine' => 17,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'usingInMemoryDatabases' => 
      array (
        'name' => 'usingInMemoryDatabases',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if any of the connections transacting is using in-memory databases.
 *
 * @return bool
 */',
        'startLine' => 35,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'usingInMemoryDatabase' => 
      array (
        'name' => 'usingInMemoryDatabase',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 51,
                'endLine' => 51,
                'startTokenPos' => 156,
                'startFilePos' => 1180,
                'endTokenPos' => 156,
                'endFilePos' => 1183,
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
                      'name' => 'string',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 46,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if a given database connection is an in-memory database.
 *
 * @return bool
 */',
        'startLine' => 51,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'restoreInMemoryDatabase' => 
      array (
        'name' => 'restoreInMemoryDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Restore the in-memory database between tests.
 *
 * @return void
 */',
        'startLine' => 65,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'refreshTestDatabase' => 
      array (
        'name' => 'refreshTestDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Refresh a conventional test database.
 *
 * @return void
 */',
        'startLine' => 81,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'updateLocalCacheOfInMemoryDatabases' => 
      array (
        'name' => 'updateLocalCacheOfInMemoryDatabases',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update locally cached in-memory PDO connections after migration.
 *
 * @return void
 */',
        'startLine' => 101,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'migrateDatabases' => 
      array (
        'name' => 'migrateDatabases',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Migrate the database.
 *
 * @return void
 */',
        'startLine' => 117,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'beginDatabaseTransaction' => 
      array (
        'name' => 'beginDatabaseTransaction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Begin a database transaction on the testing database.
 *
 * @return void
 */',
        'startLine' => 127,
        'endLine' => 167,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'connectionsToTransact' => 
      array (
        'name' => 'connectionsToTransact',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The database connections that should have transactions.
 *
 * @return array
 */',
        'startLine' => 174,
        'endLine' => 179,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'beforeRefreshingDatabase' => 
      array (
        'name' => 'beforeRefreshingDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Perform any work that should take place before the database has started refreshing.
 *
 * @return void
 */',
        'startLine' => 186,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'aliasName' => NULL,
      ),
      'afterRefreshingDatabase' => 
      array (
        'name' => 'afterRefreshingDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Perform any work that should take place once the database has finished refreshing.
 *
 * @return void
 */',
        'startLine' => 196,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Testing',
        'declaringClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'implementingClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
        'currentClassName' => 'Illuminate\\Foundation\\Testing\\RefreshDatabase',
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