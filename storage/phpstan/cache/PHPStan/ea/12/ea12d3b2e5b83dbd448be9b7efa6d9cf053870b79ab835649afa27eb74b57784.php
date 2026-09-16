<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminActivityFeedRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminActivityFeedRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-903c479db8458dc864a376f36f1d8beeb512230dda47a1ef7a8525e09ca147be',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminActivityFeedRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
    'shortName' => 'AdminActivityFeedRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validated query parameters for the Administrative Audit Trail
 * (US-709, §27.0). Every filter is server-side and reaches the feed only in
 * validated form, mirroring ActivityFeedRequest\'s discipline for the /activity
 * page. \'actor\' is a legitimate validated filter here — the trail is
 * system-wide and admin-only, and the actor filter only narrows it — while the
 * untrusted user-scoping spellings (user_id/userId/user/owner) are rejected at
 * the controller as probes before any data-layer work (§42/§44).
 *
 * Unlike the activity timeline, the audit trail is read straight from the
 * append-only AdminAudit table, which is indexed on admin_user_id, action,
 * created_at, and (target_type, target_id). There is no computed over-history
 * span to bound, so from/to are optional and only narrow the query; when both
 * are present, to must not precede from (checked in withValidator so a missing
 * bound never trips the comparison).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 65,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Foundation\\Http\\FormRequest',
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
    ),
    'immediateMethods' => 
    array (
      'authorize' => 
      array (
        'name' => 'authorize',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'aliasName' => NULL,
      ),
      'withValidator' => 
      array (
        'name' => 'withValidator',
        'parameters' => 
        array (
          'validator' => 
          array (
            'name' => 'validator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Validation\\Validator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 35,
            'endColumn' => 54,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
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
 * @return array<string, mixed>
 */',
        'startLine' => 55,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminActivityFeedRequest',
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