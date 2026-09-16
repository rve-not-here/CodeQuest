<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/CourseService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\CourseService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-5f39441b6eb96381f1c7744bf17694c5097f3522c270229924036cc7ce034806',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\CourseService',
        'filename' => '/home/notdotguy/codequest/app/Services/CourseService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\CourseService',
    'shortName' => 'CourseService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The only write path for the course catalog (US-705, §15.0–§17.0). Courses
 * are listed in order_num order (the single ordering mechanism; no second
 * sort is invented), and update() writes ONLY the course row — a name,
 * slug, type, description, status, or order_num change never touches
 * the404_progress, so no course edit can silently rewrite a student\'s
 * per-mission progress.
 *
 * course.status (active|locked|draft) is an ACCESS GATE (US-705, confirmed
 * by the user): \'locked\' and \'draft\' seal the course\'s missions (Mission
 * Controller ::ensureCourseActive, 403) and its Boss Challenge (Assessment
 * Service ::isUnlocked requires course.status === \'active\', surfaced as
 * "Challenge sealed"), so a directly-URL\'d mission can no longer accrue
 * progress, XP, or achievements under a sealed course. Locking also removes
 * the course from every status=\'active\' consumer (DashboardService::
 * currentCourse, CompetencyService, CourseAnalyticsService universe,
 * AchievementService full_clear, RecommendationService, AttentionService).
 * Progress recorded before the seal is preserved; only NEW use is blocked.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 206,
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
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'active\', \'locked\', \'draft\']',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 45,
            'startFilePos' => 1587,
            'endTokenPos' => 53,
            'endFilePos' => 1615,
          ),
        ),
        'docComment' => '/**
 * The exact set of catalog statuses an update may assign, mirrored from
 * the the404_courses enum column and enforced here as defense-in-depth
 * (the FormRequest validates first). Server-determined; no free strings.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
      'audit' => 
      array (
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
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
        'startLine' => 39,
        'endLine' => 39,
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
            'startLine' => 39,
            'endLine' => 39,
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
        'startLine' => 38,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'currentClassName' => 'App\\Services\\CourseService',
        'aliasName' => NULL,
      ),
      'ordered' => 
      array (
        'name' => 'ordered',
        'parameters' => 
        array (
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
 * The whole catalog in progression order. The listing\'s only ordering
 * key is order_num (preserved from the existing data model — no second
 * ordering mechanism is introduced).
 *
 * @return Collection<int, Course>
 */',
        'startLine' => 49,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'currentClassName' => 'App\\Services\\CourseService',
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
            'startLine' => 74,
            'endLine' => 74,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 1,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 57,
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
            'name' => 'App\\Models\\Course',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update a course\'s editable fields plus its optional status. Guards and
 * allow-list checks run BEFORE any write, so a refused update leaves the
 * course untouched. Only the course row is written; student Progress rows
 * are never selected, deleted, or rewritten here.
 *
 * An actual status change is recorded as \'course.status.change\'; any
 * actual non-status field change is recorded as \'course.update\'. An
 * unchanged submission (no-op round-trip) records nothing, mirroring
 * UserService. A value outside the allow lists is recorded as a \'failed\'
 * audit row for the course before InvalidArgumentException is thrown, so
 * a rejected attempt is never silent.
 *
 * @param  array{name: string, slug: string, type: string, description?: string|null, status: string, order_num: mixed}  $attributes
 */',
        'startLine' => 74,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'currentClassName' => 'App\\Services\\CourseService',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 29,
            'endColumn' => 39,
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 42,
            'endColumn' => 55,
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 58,
            'endColumn' => 71,
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
 * Record a failed course-update attempt, then throw. Consistent with
 * UserService\'s refusal flow: the ledger carries the row before the
 * exception propagates.
 *
 * @return never
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 159,
        'endLine' => 171,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'currentClassName' => 'App\\Services\\CourseService',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 42,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 57,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 71,
            'endColumn' => 82,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 85,
            'endColumn' => 96,
            'parameterIndex' => 3,
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 99,
            'endColumn' => 117,
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 120,
            'endColumn' => 132,
            'parameterIndex' => 5,
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
 * A compact "field → value" list of the non-status fields that actually
 * changed, or null when nothing non-status changed (a no-op round-trip
 * records no audit row).
 *
 * @param  array{name: string, slug: string, type: string, description: string, order_num: int}  $before
 */',
        'startLine' => 180,
        'endLine' => 205,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\CourseService',
        'implementingClassName' => 'App\\Services\\CourseService',
        'currentClassName' => 'App\\Services\\CourseService',
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