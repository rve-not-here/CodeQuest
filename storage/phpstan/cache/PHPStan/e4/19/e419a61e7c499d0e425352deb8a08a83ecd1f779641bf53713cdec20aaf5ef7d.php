<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Exceptions/UserProtectionException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Exceptions\UserProtectionException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-580f869b312e057a024c08157a96ae4c1986d52588c37c73143435eda706bdf8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Exceptions\\UserProtectionException',
        'filename' => '/home/notdotguy/codequest/app/Exceptions/UserProtectionException.php',
      ),
    ),
    'namespace' => 'App\\Exceptions',
    'name' => 'App\\Exceptions\\UserProtectionException',
    'shortName' => 'UserProtectionException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when a role or status change on an existing account is refused by
 * the US-704/§13 write-path guards: an admin changing their own role away
 * from admin, deactivating their own account, or a change that would leave
 * the active-admin fleet below two. The refusal is always recorded in the
 * admin audit trail as a failed entry BEFORE this throws, so a blocked
 * change is never silent.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 31,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
      'selfRoleChange' => 
      array (
        'name' => 'selfRoleChange',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Exceptions',
        'declaringClassName' => 'App\\Exceptions\\UserProtectionException',
        'implementingClassName' => 'App\\Exceptions\\UserProtectionException',
        'currentClassName' => 'App\\Exceptions\\UserProtectionException',
        'aliasName' => NULL,
      ),
      'selfDeactivation' => 
      array (
        'name' => 'selfDeactivation',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
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
        'modifiers' => 17,
        'namespace' => 'App\\Exceptions',
        'declaringClassName' => 'App\\Exceptions\\UserProtectionException',
        'implementingClassName' => 'App\\Exceptions\\UserProtectionException',
        'currentClassName' => 'App\\Exceptions\\UserProtectionException',
        'aliasName' => NULL,
      ),
      'lastActiveAdmin' => 
      array (
        'name' => 'lastActiveAdmin',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Exceptions',
        'declaringClassName' => 'App\\Exceptions\\UserProtectionException',
        'implementingClassName' => 'App\\Exceptions\\UserProtectionException',
        'currentClassName' => 'App\\Exceptions\\UserProtectionException',
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