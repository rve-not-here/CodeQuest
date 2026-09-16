<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Exceptions/AssessmentAlreadyExistsException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Exceptions\AssessmentAlreadyExistsException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-95ae39a021470e5ec6d0aa1672b1655fb1122a279ee171f42a3356cb86049bf0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Exceptions\\AssessmentAlreadyExistsException',
        'filename' => '/home/notdotguy/codequest/app/Exceptions/AssessmentAlreadyExistsException.php',
      ),
    ),
    'namespace' => 'App\\Exceptions',
    'name' => 'App\\Exceptions\\AssessmentAlreadyExistsException',
    'shortName' => 'AssessmentAlreadyExistsException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when an attempt is made to create a second assessment for a course
 * that already has one. Each course has exactly one Boss Challenge (§3.2).
 * Thrown at the application layer so callers get a clear domain error instead
 * of the raw database unique-constraint violation surfacing.
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
      'forCourse' => 
      array (
        'name' => 'forCourse',
        'parameters' => 
        array (
          'courseId' => 
          array (
            'name' => 'courseId',
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
            'startColumn' => 38,
            'endColumn' => 50,
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
        'declaringClassName' => 'App\\Exceptions\\AssessmentAlreadyExistsException',
        'implementingClassName' => 'App\\Exceptions\\AssessmentAlreadyExistsException',
        'currentClassName' => 'App\\Exceptions\\AssessmentAlreadyExistsException',
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