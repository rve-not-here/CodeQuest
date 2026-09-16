<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/AdminAudit.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\AdminAudit
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-0348c664eb15fab3b7d10b42e57e07040564bf77976fa2f96abe622528b669d3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\AdminAudit',
        'filename' => '/home/notdotguy/codequest/app/Models/AdminAudit.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\AdminAudit',
    'shortName' => 'AdminAudit',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One append-only row per administrative action (US-702/US-709, §28). The
 * dedicated the404_admin_audit table is the ONLY source for "recent system
 * activity" on the admin console — never TimelineService, whose learning-beat
 * vocabulary deliberately excludes admin bookkeeping.
 *
 * @property int $admin_user_id
 * @property string $admin_username
 * @property string $action
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $summary
 * @property string $result
 * @property Carbon $created_at
 */',
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
            'code' => '[\'admin_user_id\', \'admin_username\', \'action\', \'target_type\', \'target_id\', \'summary\', \'result\']',
            'attributes' => 
            array (
              'startLine' => 24,
              'endLine' => 32,
              'startTokenPos' => 27,
              'startFilePos' => 722,
              'endTokenPos' => 50,
              'endFilePos' => 846,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 24,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
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
      'table' => 
      array (
        'declaringClassName' => 'App\\Models\\AdminAudit',
        'implementingClassName' => 'App\\Models\\AdminAudit',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_admin_audit\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 70,
            'startFilePos' => 906,
            'endTokenPos' => 70,
            'endFilePos' => 925,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 44,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timestamps' => 
      array (
        'declaringClassName' => 'App\\Models\\AdminAudit',
        'implementingClassName' => 'App\\Models\\AdminAudit',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 81,
            'startFilePos' => 1041,
            'endTokenPos' => 81,
            'endFilePos' => 1045,
          ),
        ),
        'docComment' => '/**
 * the404_admin_audit is append-only and has no updated_at column.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
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
        'startLine' => 42,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\AdminAudit',
        'implementingClassName' => 'App\\Models\\AdminAudit',
        'currentClassName' => 'App\\Models\\AdminAudit',
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