<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Exceptions/AssessmentNotUnlockedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Exceptions\AssessmentNotUnlockedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-b9ecca14e52d9f89265c343c504c8f69d8a920c08ae2277197f432654c88c09e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Exceptions\\AssessmentNotUnlockedException',
        'filename' => '/home/notdotguy/codequest/app/Exceptions/AssessmentNotUnlockedException.php',
      ),
    ),
    'namespace' => 'App\\Exceptions',
    'name' => 'App\\Exceptions\\AssessmentNotUnlockedException',
    'shortName' => 'AssessmentNotUnlockedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when a student tries to begin a Boss Challenge they are not unlocked
 * for (§6/US-404). The attempt is refused at the application layer so the
 * gating lives in the service, never the view.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 18,
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
            'startLine' => 14,
            'endLine' => 14,
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
        'startLine' => 14,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Exceptions',
        'declaringClassName' => 'App\\Exceptions\\AssessmentNotUnlockedException',
        'implementingClassName' => 'App\\Exceptions\\AssessmentNotUnlockedException',
        'currentClassName' => 'App\\Exceptions\\AssessmentNotUnlockedException',
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