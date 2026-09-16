<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/XpTransaction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\XpTransaction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-45d388bf76d7fa273fff10e6a5ebba7dfac3522b511c01035710e88ebbfb29e1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\XpTransaction',
        'filename' => '/home/notdotguy/codequest/app/Models/XpTransaction.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\XpTransaction',
    'shortName' => 'XpTransaction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Illuminate\\Database\\Eloquent\\Attributes\\Fillable',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '[\'user_id\', \'mission_id\', \'assessment_id\', \'amount\', \'type\', \'description\']',
            'attributes' => 
            array (
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 30,
              'startFilePos' => 245,
              'endTokenPos' => 47,
              'endFilePos' => 319,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 10,
    'endLine' => 44,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'table' => 
      array (
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_xp_transactions\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 72,
            'startFilePos' => 403,
            'endTokenPos' => 72,
            'endFilePos' => 426,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timestamps' => 
      array (
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 83,
            'startFilePos' => 527,
            'endTokenPos' => 83,
            'endFilePos' => 531,
          ),
        ),
        'docComment' => '/**
 * the404_xp_transactions has no updated_at column.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'casts' => 
      array (
        'name' => 'casts',
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
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'currentClassName' => 'App\\Models\\XpTransaction',
        'aliasName' => NULL,
      ),
      'user' => 
      array (
        'name' => 'user',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'currentClassName' => 'App\\Models\\XpTransaction',
        'aliasName' => NULL,
      ),
      'mission' => 
      array (
        'name' => 'mission',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'currentClassName' => 'App\\Models\\XpTransaction',
        'aliasName' => NULL,
      ),
      'assessment' => 
      array (
        'name' => 'assessment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\XpTransaction',
        'implementingClassName' => 'App\\Models\\XpTransaction',
        'currentClassName' => 'App\\Models\\XpTransaction',
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