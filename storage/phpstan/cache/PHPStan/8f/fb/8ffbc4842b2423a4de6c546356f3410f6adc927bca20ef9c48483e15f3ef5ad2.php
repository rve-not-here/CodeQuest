<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminMissionUpdateRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminMissionUpdateRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-c5c1bc49585e477dbe65208f7205b99dee4a98d8623aca7000499dd1117beb91',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminMissionUpdateRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
    'shortName' => 'AdminMissionUpdateRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Mission update payload (US-707, §19.0). Missions carry no status field (the
 * access gate sits on course.status, US-705), so there is no status input.
 * The nine editable fields mirror the AdminMissionService allow list: title,
 * description, difficulty (from the EASY/MEDIUM/HARD enum), points and
 * order_num (non-negative integers), section_id (nullable — a cross-course
 * section is checked at the service layer, the schema FK alone does not stop
 * it), hints, broken_code, and target_html. solution_code and validate_rule
 * are deliberately NOT present: they are view-only this story and can never
 * be written even by a crafted payload. The guarded AdminMissionService write
 * path re-checks the allow list on top of this shape validation.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 59,
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
        'declaringClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
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
 * Normalize the section select before validation. HTML forms submit the
 * option value as a numeric string (and "NO SECTION" as an empty string,
 * already nulled by the ConvertEmptyStringsToNull middleware); the guarded
 * service requires a real int or null, so coerce here rather than forcing
 * the service to widen its type.
 */',
        'startLine' => 35,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
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
        'startLine' => 45,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminMissionUpdateRequest',
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