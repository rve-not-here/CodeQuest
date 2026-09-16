<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminUserUpdateRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminUserUpdateRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-bf4b0d98fdb9f583a6a5833a267c926911faec23fb6988600350b4acdbed9c32',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminUserUpdateRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
    'shortName' => 'AdminUserUpdateRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Account update payload (US-703, §12.0; role/status US-704, §13). Username
 * and name are always writable; password is optional (blank keeps the current
 * hash). Role and status are OPTIONAL too but server-determined: role changes
 * validate against the exact UserService::ROLES set (operator included on
 * existing accounts), status against active|inactive. The guarded service
 * write path enforces self-protection and the ≥2-active-admin floor on top of
 * this shape validation.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 60,
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
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
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
        'startLine' => 30,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'aliasName' => NULL,
      ),
      'rejectPreHashedPassword' => 
      array (
        'name' => 'rejectPreHashedPassword',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Closure',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Same pre-hashed-password guard as AdminUserStoreRequest.
 */',
        'startLine' => 52,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserUpdateRequest',
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