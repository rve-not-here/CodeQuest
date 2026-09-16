<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AdminMissionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AdminMissionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-7526f9c0e5aa81ded55e0df9b6b152724f286e48350b1b33753c30e1e51edebe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AdminMissionService',
        'filename' => '/home/notdotguy/codequest/app/Services/AdminMissionService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AdminMissionService',
    'shortName' => 'AdminMissionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The only admin write path for missions (US-707, §19.0). Missions are the
 * leaves of the Course → Section → Mission hierarchy and carry NO status of
 * their own — the access gate for everything beneath a course sits on
 * course.status (US-705) and assessment.status, never on the mission row, so
 * there is no status field to manage here.
 *
 * update() writes ONLY the mission\'s editable fields (title, description,
 * difficulty, points, order_num, section_id, hints, broken_code, target_html).
 * solution_code and validate_rule are deliberately NOT writable in this story
 * (§19 view-only: the editing of the reference solution and validation rules
 * is deferred); they can never pass through this write path even if a crafted
 * payload smuggles them. The write never touches the404_progress, the
 * the404_xp_transactions, or the404_activity, so changing points (or any
 * field) never rewrites history: rows already on the books keep the points
 * they were awarded at completion time — only future completions see the new
 * value.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 284,
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
      'DIFFICULTIES' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'name' => 'DIFFICULTIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'EASY\', \'MEDIUM\', \'HARD\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 55,
            'startFilePos' => 1577,
            'endTokenPos' => 63,
            'endFilePos' => 1602,
          ),
        ),
        'docComment' => '/**
 * The mission difficulty names, mirrored from the the404_missions enum
 * column and enforced here as defense-in-depth (the FormRequest validates
 * first). Server-determined; no free strings.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 59,
      ),
    ),
    'immediateProperties' => 
    array (
      'audit' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'name' => 'audit',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AdminAuditService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'audit' => 
          array (
            'name' => 'audit',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AdminAuditService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 9,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'currentClassName' => 'App\\Services\\AdminMissionService',
        'aliasName' => NULL,
      ),
      'forCourse' => 
      array (
        'name' => 'forCourse',
        'parameters' => 
        array (
          'course' => 
          array (
            'name' => 'course',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Course',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 31,
            'endColumn' => 44,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The missions of one course in display order, with their section. The
 * listing\'s ordering key is order_num then id — the same single ordering
 * mechanism the learning path uses.
 *
 * @return Collection<int, Mission>
 */',
        'startLine' => 52,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'currentClassName' => 'App\\Services\\AdminMissionService',
        'aliasName' => NULL,
      ),
      'update' => 
      array (
        'name' => 'update',
        'parameters' => 
        array (
          'actor' => 
          array (
            'name' => 'actor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'course' => 
          array (
            'name' => 'course',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Course',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'mission' => 
          array (
            'name' => 'mission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Mission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 57,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 75,
            'endColumn' => 91,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\Mission',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update a mission\'s editable fields. Guards and allow-list checks run
 * BEFORE any write, so a refused update leaves the mission untouched: the
 * mission must belong to the given course (the nested {course}/missions/
 * {mission} route must not let a mission leak across courses), difficulty
 * must be one of DIFFICULTIES, points and order_num non-negative integers,
 * and a section_id must reference a real section of the SAME course as the
 * mission (the schema\'s FK alone does not stop a cross-course assignment —
 * a composite (course_id, section_id) constraint does not exist, so the
 * value is enforced here).
 *
 * An actual change records \'mission.update\'; a no-op round-trip records
 * nothing, mirroring CourseService and SectionService. A value outside the
 * allow lists records a \'failed\' audit row before InvalidArgumentException
 * is thrown, so a rejected attempt is never silent.
 *
 * @param  array{
 *     title: string,
 *     description?: string|null,
 *     difficulty: string,
 *     points: mixed,
 *     order_num: mixed,
 *     section_id: int|null,
 *     hints?: string|null,
 *     broken_code?: string|null,
 *     target_html?: string|null,
 * }  $attributes
 */',
        'startLine' => 90,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'currentClassName' => 'App\\Services\\AdminMissionService',
        'aliasName' => NULL,
      ),
      'refuse' => 
      array (
        'name' => 'refuse',
        'parameters' => 
        array (
          'actor' => 
          array (
            'name' => 'actor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mission' => 
          array (
            'name' => 'mission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Mission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 42,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 60,
            'endColumn' => 73,
            'parameterIndex' => 2,
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
        'docComment' => '/**
 * Record a failed mission-update attempt, then throw. Consistent with
 * CourseService\'s and SectionService\'s refusal flow: the ledger carries
 * the row before the exception propagates.
 *
 * @return never
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 200,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'currentClassName' => 'App\\Services\\AdminMissionService',
        'aliasName' => NULL,
      ),
      'fieldChangesSummary' => 
      array (
        'name' => 'fieldChangesSummary',
        'parameters' => 
        array (
          'before' => 
          array (
            'name' => 'before',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'title' => 
          array (
            'name' => 'title',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'description' => 
          array (
            'name' => 'description',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'difficulty' => 
          array (
            'name' => 'difficulty',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'points' => 
          array (
            'name' => 'points',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'orderNum' => 
          array (
            'name' => 'orderNum',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'sectionId' => 
          array (
            'name' => 'sectionId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 9,
            'endColumn' => 24,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'hints' => 
          array (
            'name' => 'hints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'brokenCode' => 
          array (
            'name' => 'brokenCode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
          'targetHtml' => 
          array (
            'name' => 'targetHtml',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 9,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * A compact "field → value" list of the editable fields that actually
 * changed, or null when nothing changed (a no-op round-trip records no
 * audit row). solution_code and validate_rule never appear here — they are
 * not writable in this story.
 *
 * @param  array{
 *     title: string,
 *     description: string,
 *     difficulty: string,
 *     points: int,
 *     order_num: int,
 *     section_id: int|null,
 *     hints: string,
 *     broken_code: string,
 *     target_html: string,
 * }  $before
 */',
        'startLine' => 232,
        'endLine' => 283,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminMissionService',
        'implementingClassName' => 'App\\Services\\AdminMissionService',
        'currentClassName' => 'App\\Services\\AdminMissionService',
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