<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Models/Announcement.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Announcement
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-bc521bab2c7376e0dfa03301616c4fa3a5676475e03eddb63981034a3cc3b9b6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Announcement',
        'filename' => '/home/notdotguy/codequest/app/Models/Announcement.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Announcement',
    'shortName' => 'Announcement',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One admin-authored system announcement (Phase 8, §30/§31). status
 * (draft|published|archived) is the publication lifecycle gate; published_at
 * is set once on the first publish. audience (all|students|teachers|admins)
 * is server-determined and decides which users receive a
 * SYSTEM_ANNOUNCEMENT notification at publish — operator is deliberately
 * excluded from every audience.
 *
 * @property int $id
 * @property int $created_by
 * @property string $title
 * @property string $message
 * @property string $audience
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
            'code' => '[\'created_by\', \'title\', \'message\', \'audience\', \'status\', \'published_at\']',
            'attributes' => 
            array (
              'startLine' => 30,
              'endLine' => 37,
              'startTokenPos' => 42,
              'startFilePos' => 991,
              'endTokenPos' => 62,
              'endFilePos' => 1089,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 30,
    'endLine' => 60,
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
        'declaringClassName' => 'App\\Models\\Announcement',
        'implementingClassName' => 'App\\Models\\Announcement',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'the404_announcements\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 89,
            'startFilePos' => 1220,
            'endTokenPos' => 89,
            'endFilePos' => 1241,
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
        'startLine' => 45,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Announcement',
        'implementingClassName' => 'App\\Models\\Announcement',
        'currentClassName' => 'App\\Models\\Announcement',
        'aliasName' => NULL,
      ),
      'creator' => 
      array (
        'name' => 'creator',
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
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Announcement',
        'implementingClassName' => 'App\\Models\\Announcement',
        'currentClassName' => 'App\\Models\\Announcement',
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