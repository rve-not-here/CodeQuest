<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/Notification.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Notification
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-dbc85ae3165d99be7b88fdb53d97a025f77ebd8bed7f34236a302fe177630476',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Notification',
        'filename' => '/home/notdotguy/codequest/app/Models/Notification.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Notification',
    'shortName' => 'Notification',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One notification row per recipient (Phase 8, §10/§39). A notification
 * belongs to exactly one user; ownership is enforced at the service layer
 * (NotificationService), never in the view. Append-only: there is no
 * updated_at column, so $timestamps is off. dedupe_key carries the
 * duplicate-prevention identity for one-shot events (unique per user); it is
 * null for frequency-governed recurring notifications.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array<array-key, mixed>|null $data
 * @property string|null $dedupe_key
 * @property Carbon|null $read_at
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
            'code' => '[\'user_id\', \'type\', \'title\', \'message\', \'data\', \'dedupe_key\']',
            'attributes' => 
            array (
              'startLine' => 30,
              'endLine' => 37,
              'startTokenPos' => 42,
              'startFilePos' => 1033,
              'endTokenPos' => 62,
              'endFilePos' => 1120,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 30,
    'endLine' => 65,
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
        'declaringClassName' => 'App\\Models\\Notification',
        'implementingClassName' => 'App\\Models\\Notification',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_notifications\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 89,
            'startFilePos' => 1251,
            'endTokenPos' => 89,
            'endFilePos' => 1272,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 46,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timestamps' => 
      array (
        'declaringClassName' => 'App\\Models\\Notification',
        'implementingClassName' => 'App\\Models\\Notification',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 100,
            'startFilePos' => 1390,
            'endTokenPos' => 100,
            'endFilePos' => 1394,
          ),
        ),
        'docComment' => '/**
 * the404_notifications is append-only and has no updated_at column.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
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
        'startLine' => 50,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Notification',
        'implementingClassName' => 'App\\Models\\Notification',
        'currentClassName' => 'App\\Models\\Notification',
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
        'startLine' => 61,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Notification',
        'implementingClassName' => 'App\\Models\\Notification',
        'currentClassName' => 'App\\Models\\Notification',
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