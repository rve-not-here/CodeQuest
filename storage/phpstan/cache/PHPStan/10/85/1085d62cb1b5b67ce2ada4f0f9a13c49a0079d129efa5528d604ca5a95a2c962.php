<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Exceptions/AssessmentAttemptAccessDeniedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Exceptions\AssessmentAttemptAccessDeniedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-71d05dd077ba1f3ddef231720e17ee8a2012175644bb2ecf5d6b350e672335a6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Exceptions\\AssessmentAttemptAccessDeniedException',
        'filename' => '/home/notdotguy/codequest/app/Exceptions/AssessmentAttemptAccessDeniedException.php',
      ),
    ),
    'namespace' => 'App\\Exceptions',
    'name' => 'App\\Exceptions\\AssessmentAttemptAccessDeniedException',
    'shortName' => 'AssessmentAttemptAccessDeniedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when a user attempts to read an assessment attempt they do not own.
 * The ownership check is reasoned explicitly in the service, so a student\'s
 * code and score are never exposed merely by a view omitting other users\'
 * records.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 19,
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
      'forAttempt' => 
      array (
        'name' => 'forAttempt',
        'parameters' => 
        array (
          'attemptId' => 
          array (
            'name' => 'attemptId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 39,
            'endColumn' => 52,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Exceptions',
        'declaringClassName' => 'App\\Exceptions\\AssessmentAttemptAccessDeniedException',
        'implementingClassName' => 'App\\Exceptions\\AssessmentAttemptAccessDeniedException',
        'currentClassName' => 'App\\Exceptions\\AssessmentAttemptAccessDeniedException',
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