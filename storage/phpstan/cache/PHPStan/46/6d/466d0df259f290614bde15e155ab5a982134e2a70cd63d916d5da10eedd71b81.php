<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminUserStoreRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminUserStoreRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-6466148cc2e1099f18982ca50ee7a4f5f85d2f19f76bdbef83d734dacd87f76c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminUserStoreRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminUserStoreRequest',
    'shortName' => 'AdminUserStoreRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Account creation payload (US-703, §11.0/§12.0). Every field is validated
 * server-side before it reaches the service. The password must be a NEW
 * plaintext value: a client never supplies a pre-hashed password, and the
 * model\'s \'hashed\' cast derives the stored hash from whatever the server
 * accepts here.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 51,
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
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
        'startLine' => 27,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
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
 * A bcrypt/argon2 hash prefix is a pre-hashed password, never a new
 * plaintext secret. Rejecting it here keeps the invariant "the server hashes
 * what the client submits" from silently double-hashing an already-hashed
 * value.
 */',
        'startLine' => 43,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminUserStoreRequest',
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