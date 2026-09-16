<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/UserAchievement.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\UserAchievement
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-fc5d74b5b1e734edf602b1df9eda6b4b7d1d2f0f47d44eb83d43a39e5afaf3ba',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\UserAchievement',
        'filename' => '/home/notdotguy/codequest/app/Models/UserAchievement.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\UserAchievement',
    'shortName' => 'UserAchievement',
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
            'code' => '[\'user_id\', \'achievement_id\']',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 35,
              'startFilePos' => 292,
              'endTokenPos' => 40,
              'endFilePos' => 320,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 43,
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
        'declaringClassName' => 'App\\Models\\UserAchievement',
        'implementingClassName' => 'App\\Models\\UserAchievement',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_user_achievements\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 67,
            'startFilePos' => 457,
            'endTokenPos' => 67,
            'endFilePos' => 482,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timestamps' => 
      array (
        'declaringClassName' => 'App\\Models\\UserAchievement',
        'implementingClassName' => 'App\\Models\\UserAchievement',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 78,
            'startFilePos' => 635,
            'endTokenPos' => 78,
            'endFilePos' => 639,
          ),
        ),
        'docComment' => '/**
 * the404_user_achievements has no updated_at column: an award is written
 * once and never edited.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
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
        'startLine' => 25,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\UserAchievement',
        'implementingClassName' => 'App\\Models\\UserAchievement',
        'currentClassName' => 'App\\Models\\UserAchievement',
        'aliasName' => NULL,
      ),
      'achievement' => 
      array (
        'name' => 'achievement',
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
        'docComment' => '/** @return BelongsTo<Achievement, $this> */',
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\UserAchievement',
        'implementingClassName' => 'App\\Models\\UserAchievement',
        'currentClassName' => 'App\\Models\\UserAchievement',
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
        'docComment' => '/** @return BelongsTo<User, $this> */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\UserAchievement',
        'implementingClassName' => 'App\\Models\\UserAchievement',
        'currentClassName' => 'App\\Models\\UserAchievement',
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