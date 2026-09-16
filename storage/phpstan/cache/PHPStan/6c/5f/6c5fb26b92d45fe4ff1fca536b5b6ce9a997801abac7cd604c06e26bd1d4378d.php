<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/Assessment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Assessment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-6c76a39ea58bca3879dfc790f286cfb76b68a0cd96e69639b5bda55edd7d9ae8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Assessment',
        'filename' => '/home/notdotguy/codequest/app/Models/Assessment.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Assessment',
    'shortName' => 'Assessment',
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
            'code' => '[\'course_id\', \'title\', \'description\', \'instructions\', \'grading_rule\', \'passing_score\', \'status\']',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 20,
              'startTokenPos' => 40,
              'startFilePos' => 349,
              'endTokenPos' => 63,
              'endFilePos' => 475,
            ),
          ),
        ),
      ),
      1 => 
      array (
        'name' => 'Illuminate\\Database\\Eloquent\\Attributes\\Hidden',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '[\'grading_rule\']',
            'attributes' => 
            array (
              'startLine' => 21,
              'endLine' => 21,
              'startTokenPos' => 70,
              'startFilePos' => 488,
              'endTokenPos' => 72,
              'endFilePos' => 503,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 45,
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
        'declaringClassName' => 'App\\Models\\Assessment',
        'implementingClassName' => 'App\\Models\\Assessment',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_assessments\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 97,
            'startFilePos' => 584,
            'endTokenPos' => 97,
            'endFilePos' => 603,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 44,
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
        'startLine' => 28,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Assessment',
        'implementingClassName' => 'App\\Models\\Assessment',
        'currentClassName' => 'App\\Models\\Assessment',
        'aliasName' => NULL,
      ),
      'course' => 
      array (
        'name' => 'course',
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
        'docComment' => '/** @return BelongsTo<Course, $this> */',
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Assessment',
        'implementingClassName' => 'App\\Models\\Assessment',
        'currentClassName' => 'App\\Models\\Assessment',
        'aliasName' => NULL,
      ),
      'attempts' => 
      array (
        'name' => 'attempts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Assessment',
        'implementingClassName' => 'App\\Models\\Assessment',
        'currentClassName' => 'App\\Models\\Assessment',
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