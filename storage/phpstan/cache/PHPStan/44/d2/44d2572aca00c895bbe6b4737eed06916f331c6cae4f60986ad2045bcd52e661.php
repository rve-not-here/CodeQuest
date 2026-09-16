<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Middleware/EnsureUserIsActive.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Middleware\EnsureUserIsActive
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-1199279700ff95a45fb1e2208bfda60c72ff00dd0ad234c2623b0b6d9594a99b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Middleware\\EnsureUserIsActive',
        'filename' => '/home/notdotguy/codequest/app/Http/Middleware/EnsureUserIsActive.php',
      ),
    ),
    'namespace' => 'App\\Http\\Middleware',
    'name' => 'App\\Http\\Middleware\\EnsureUserIsActive',
    'shortName' => 'EnsureUserIsActive',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 41,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Immediate lockout for deactivated accounts (US-701, §13/§14). Runs on
 * every web request, after the session starts: an authenticated user whose
 * status is no longer \'active\' is logged out, the session is killed, and
 * the request is bounced to login. The account is locked out on the very
 * next request after deactivation, not just at the next sign-in attempt.
 *
 * Guests (no resolved user) pass straight through; the route-level \'auth\'
 * middleware handles the guest redirect afterwards.
 *
 * @param  Closure(Request): (Response)  $next
 */',
        'startLine' => 24,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Middleware',
        'declaringClassName' => 'App\\Http\\Middleware\\EnsureUserIsActive',
        'implementingClassName' => 'App\\Http\\Middleware\\EnsureUserIsActive',
        'currentClassName' => 'App\\Http\\Middleware\\EnsureUserIsActive',
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