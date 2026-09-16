<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AdminAssessmentService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AdminAssessmentService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-4e66f6dcf0e78a4429bd92523310d09a999bfcb7bbcbe4ab620f466114ebfffa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AdminAssessmentService',
        'filename' => '/home/notdotguy/codequest/app/Services/AdminAssessmentService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AdminAssessmentService',
    'shortName' => 'AdminAssessmentService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The only admin write path for a course\'s Boss Challenge (US-708, §24.0).
 * One course has exactly one assessment — the the404_assessments unique
 * course_id constraint (§3.2) — so the write target is always a single row.
 *
 * update() writes ONLY the assessment\'s editable fields (title, description,
 * instructions, passing_score, status). grading_rule is deliberately NOT
 * writable in this story (§24 view-only: the grading rules engine is authored
 * via the AssessmentSeeder, the same controlled path as solution_code and
 * validate_rule on missions); it can never pass through this write path even
 * if a crafted payload smuggles it. The write never touches the404_assessment_
 * attempts, the404_xp_transactions, the404_progress, or course completion
 * (§26.0: pass/fail, score, attempt results, and course completion are academic
 * records, not admin-editable configuration — they stay derived from attempt
 * history, never rewritten here).
 *
 * assessment.status (active|locked|draft) is an ACCESS GATE, exactly like
 * course.status (US-705): \'locked\' and \'draft\' seal the Boss Challenge
 * (AssessmentService::isUnlocked requires assessment.status === \'active\',
 * surfaced to students as "Challenge sealed"). Sealing never rewrites recorded
 * attempts — it only blocks new use. passing_score edits are never retroactive
 * either: evaluateAttempt reads it at evaluation time, but score/status are
 * already persisted to the attempt row, so a threshold edit changes only
 * FUTURE submissions, never a verdict already on the books.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 216,
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
      'STATUSES' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'active\', \'locked\', \'draft\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 45,
            'startFilePos' => 2026,
            'endTokenPos' => 53,
            'endFilePos' => 2054,
          ),
        ),
        'docComment' => '/**
 * The exact set of assessment statuses an update may assign, mirrored from
 * the the404_assessments enum column and enforced here as defense-in-depth
 * (the FormRequest validates first). Server-determined; no free strings.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
      'audit' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
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
        'startLine' => 45,
        'endLine' => 45,
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
            'startLine' => 45,
            'endLine' => 45,
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
        'startLine' => 44,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'currentClassName' => 'App\\Services\\AdminAssessmentService',
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
                  'name' => 'App\\Models\\Assessment',
                  'isIdentifier' => false,
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
 * The single Boss Challenge of one course, if one exists. The unique
 * course_id constraint guarantees at most one row.
 */',
        'startLine' => 52,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'currentClassName' => 'App\\Services\\AdminAssessmentService',
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
            'startLine' => 82,
            'endLine' => 82,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'assessment' => 
          array (
            'name' => 'assessment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Assessment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 57,
            'endColumn' => 78,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 81,
            'endColumn' => 97,
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
            'name' => 'App\\Models\\Assessment',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update a course\'s Boss Challenge. Guards and allow-list checks run
 * BEFORE any write, so a refused update leaves the assessment untouched:
 * the assessment must belong to the given course (the nested
 * {course}/assessment/{assessment} route must not let a challenge leak
 * across courses), title non-empty at most 128 chars, passing_score a
 * non-negative integer, status one of STATUSES.
 *
 * An actual change records \'assessment.update\'; a status change records a
 * separate \'assessment.status.change\' row (mirroring CourseService); a
 * no-op round-trip records nothing. A value outside the allow lists, or a
 * cross-course assessment, records a \'failed\' audit row before
 * InvalidArgumentException is thrown, so a rejected attempt is never
 * silent.
 *
 * @param  array{
 *     title: string,
 *     description?: string|null,
 *     instructions?: string|null,
 *     passing_score: mixed,
 *     status: string,
 * }  $attributes
 */',
        'startLine' => 82,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'currentClassName' => 'App\\Services\\AdminAssessmentService',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'assessment' => 
          array (
            'name' => 'assessment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Assessment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 42,
            'endColumn' => 63,
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 66,
            'endColumn' => 79,
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
 * Record a failed assessment-update attempt, then throw. Consistent with
 * CourseService\'s and SectionService\'s refusal flow: the ledger carries the
 * row before the exception propagates.
 *
 * @return never
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 161,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'currentClassName' => 'App\\Services\\AdminAssessmentService',
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
            'startLine' => 190,
            'endLine' => 190,
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
            'startLine' => 191,
            'endLine' => 191,
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
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'instructions' => 
          array (
            'name' => 'instructions',
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
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'passingScore' => 
          array (
            'name' => 'passingScore',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 4,
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
 * audit row). Status is excluded here — it is recorded separately as
 * \'assessment.status.change\'. grading_rule never appears: it is not
 * writable.
 *
 * @param  array{
 *     title: string,
 *     description: string,
 *     instructions: string,
 *     passing_score: int,
 * }  $before
 */',
        'startLine' => 189,
        'endLine' => 215,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAssessmentService',
        'implementingClassName' => 'App\\Services\\AdminAssessmentService',
        'currentClassName' => 'App\\Services\\AdminAssessmentService',
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