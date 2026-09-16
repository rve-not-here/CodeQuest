<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/ActivityFeedRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\ActivityFeedRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-925b62bf8e997c06b5779eb68ff73846d9b3aaebb4d60e2d3761a49b4d578ba9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/ActivityFeedRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\ActivityFeedRequest',
    'shortName' => 'ActivityFeedRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validated query parameters for the Learning Activity feed (US-606). Every
 * filter is server-side and reaches the feed only in validated form. The date
 * window is anchored in prepareForValidation: a missing bound defaults to
 * today, and a missing lower bound falls back to a trailing
 * DEFAULT_WINDOW_DAYS span, so the unfiltered view is always bounded rather
 * than computed over the whole history. The span itself is capped at
 * MAX_WINDOW_DAYS in withValidator, so a client cannot widen the window into
 * an unbounded computation (§44/§45).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 74,
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
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'aliasName' => NULL,
      ),
      'prepareForValidation' => 
      array (
        'name' => 'prepareForValidation',
        'parameters' => 
        array (
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
        'startLine' => 28,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
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
            'startLine' => 39,
            'endLine' => 39,
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
        'startLine' => 39,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
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
        'startLine' => 64,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'implementingClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
        'currentClassName' => 'App\\Http\\Requests\\ActivityFeedRequest',
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