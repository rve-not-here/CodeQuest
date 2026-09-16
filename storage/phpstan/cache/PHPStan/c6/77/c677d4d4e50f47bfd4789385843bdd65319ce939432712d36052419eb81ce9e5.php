<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminCourseUpdateRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminCourseUpdateRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-0ab75f82532e39d9f188b51322a8f2c7c244f43fe7668e7ffcd25ec59922e2fd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminCourseUpdateRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
    'shortName' => 'AdminCourseUpdateRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Course catalog update payload (US-705, §15.0–§17.0). Every editable course
 * field is server-validated here: slug is kebab-case and unique among courses
 * (ignoring self), type is a short free string (CompetencyService degrades
 * gracefully with an uppercase fallback for unknown types), status is clamped
 * to CourseService::STATUSES (active|locked|draft), and order_num is a
 * non-negative integer. The guarded CourseService write path re-checks the
 * allow lists and enforces the never-rewrite-progress invariant on top of this
 * shape validation.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 50,
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
        'declaringClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
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
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminCourseUpdateRequest',
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