<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/Mission.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Mission
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-228e5f8743de795ff69db780298e1c86000916ffe0cd65ee0d280fba3bc8e0d0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Mission',
        'filename' => '/home/notdotguy/codequest/app/Models/Mission.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Mission',
    'shortName' => 'Mission',
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
            'code' => '[\'course_id\', \'section_id\', \'order_num\', \'title\', \'difficulty\', \'description\', \'broken_code\', \'solution_code\', \'target_html\', \'validate_rule\', \'hints\', \'points\']',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 25,
              'startTokenPos' => 40,
              'startFilePos' => 348,
              'endTokenPos' => 78,
              'endFilePos' => 559,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 66,
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
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_missions\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 103,
            'startFilePos' => 637,
            'endTokenPos' => 103,
            'endFilePos' => 653,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 41,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'solution_code\', \'validate_rule\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 114,
            'startFilePos' => 960,
            'endTokenPos' => 119,
            'endFilePos' => 993,
          ),
        ),
        'docComment' => '/**
 * Never serialize the reference solution or validation rules. A student
 * must not be able to read either through any JSON/array dump of a
 * mission, only ask for the solution through the XP-gated reveal flow.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 59,
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
        'docComment' => '/**
 * @return BelongsTo<Course, $this>
 */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'currentClassName' => 'App\\Models\\Mission',
        'aliasName' => NULL,
      ),
      'section' => 
      array (
        'name' => 'section',
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
        'docComment' => '/**
 * @return BelongsTo<Section, $this>
 */',
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'currentClassName' => 'App\\Models\\Mission',
        'aliasName' => NULL,
      ),
      'progress' => 
      array (
        'name' => 'progress',
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
        'startLine' => 57,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'currentClassName' => 'App\\Models\\Mission',
        'aliasName' => NULL,
      ),
      'draft' => 
      array (
        'name' => 'draft',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOne',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Mission',
        'implementingClassName' => 'App\\Models\\Mission',
        'currentClassName' => 'App\\Models\\Mission',
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