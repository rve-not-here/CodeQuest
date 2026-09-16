<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminAssessmentUpdateRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminAssessmentUpdateRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-44083c25679ec3e873fc6f48b091e080c2cb7edff9d4b360d12336a53b63825a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminAssessmentUpdateRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
    'shortName' => 'AdminAssessmentUpdateRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Assessment update payload (US-708, §24.0). One course has exactly one
 * assessment (the the404_assessments unique course_id constraint), so all five
 * editable fields mirror the AdminAssessmentService allow list: title,
 * description, instructions, passing_score (non-negative integer), and status
 * (from the active|locked|draft enum). grading_rule is deliberately NOT
 * present: it is view-only this story — grading rules are authored through the
 * controlled AssessmentSeeder, the same path as solution_code/validate_rule on
 * missions — and can never be written even by a crafted payload. The guarded
 * AdminAssessmentService write path re-checks the allow list on top of this
 * shape validation.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 54,
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
        'declaringClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
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
        'docComment' => '/**
 * Normalize the passing_score input before validation. HTML forms submit
 * the number field as a numeric string; the guarded service requires a
 * real int, so coerce here rather than forcing the service to widen its
 * type (same pattern as section_id on missions).
 */',
        'startLine' => 34,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
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
        'startLine' => 44,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminAssessmentUpdateRequest',
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