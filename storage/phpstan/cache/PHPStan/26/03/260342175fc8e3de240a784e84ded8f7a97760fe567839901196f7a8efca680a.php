<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AdminAuditService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AdminAuditService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-22ae709c4ba64efed957bbafc3a01c0399f9b9c72e32f714fae636b43dfcff05',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AdminAuditService',
        'filename' => '/home/notdotguy/codequest/app/Services/AdminAuditService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AdminAuditService',
    'shortName' => 'AdminAuditService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Admin audit trail. recent() is the US-702 read side for the console\'s
 * "Recent system activity"; record() is the write side and the ONLY writer in
 * the codebase. feed() is the US-709 read side for the full trail on
 * /admin/activity. The guarded write paths call record() for every attempted
 * change: UserService (US-704, role/status; US-709, create and base fields)
 * and CourseService (US-705)/SectionService (US-706)/AdminMissionService
 * (US-707)/AdminAssessmentService (US-708) record \'success\' rows for applied
 * changes and \'failed\' rows for refusals, so a blocked change is never silent.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 183,
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
      'ACTION_USER_CREATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_USER_CREATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'user.create\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 53,
            'startFilePos' => 901,
            'endTokenPos' => 53,
            'endFilePos' => 913,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'ACTION_USER_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_USER_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'user.update\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 64,
            'startFilePos' => 955,
            'endTokenPos' => 64,
            'endFilePos' => 967,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'ACTION_ROLE_CHANGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ROLE_CHANGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'user.role.change\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 75,
            'startFilePos' => 1009,
            'endTokenPos' => 75,
            'endFilePos' => 1026,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
      'ACTION_STATUS_CHANGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_STATUS_CHANGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'user.status.change\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 86,
            'startFilePos' => 1070,
            'endTokenPos' => 86,
            'endFilePos' => 1089,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 61,
      ),
      'ACTION_COURSE_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_COURSE_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'course.update\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 97,
            'startFilePos' => 1133,
            'endTokenPos' => 97,
            'endFilePos' => 1147,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
      'ACTION_COURSE_STATUS_CHANGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_COURSE_STATUS_CHANGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'course.status.change\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 108,
            'startFilePos' => 1198,
            'endTokenPos' => 108,
            'endFilePos' => 1219,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 70,
      ),
      'ACTION_SECTION_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_SECTION_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'section.update\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 119,
            'startFilePos' => 1264,
            'endTokenPos' => 119,
            'endFilePos' => 1279,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'ACTION_MISSION_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_MISSION_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mission.update\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 130,
            'startFilePos' => 1324,
            'endTokenPos' => 130,
            'endFilePos' => 1339,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'ACTION_ASSESSMENT_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ASSESSMENT_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment.update\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 141,
            'startFilePos' => 1387,
            'endTokenPos' => 141,
            'endFilePos' => 1405,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
      'ACTION_ASSESSMENT_STATUS_CHANGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ASSESSMENT_STATUS_CHANGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment.status.change\'',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 152,
            'startFilePos' => 1460,
            'endTokenPos' => 152,
            'endFilePos' => 1485,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 78,
      ),
      'ACTION_ANNOUNCEMENT_CREATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ANNOUNCEMENT_CREATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'announcement.create\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 163,
            'startFilePos' => 1535,
            'endTokenPos' => 163,
            'endFilePos' => 1555,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
      'ACTION_ANNOUNCEMENT_UPDATE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ANNOUNCEMENT_UPDATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'announcement.update\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 174,
            'startFilePos' => 1605,
            'endTokenPos' => 174,
            'endFilePos' => 1625,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
      'ACTION_ANNOUNCEMENT_PUBLISH' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ANNOUNCEMENT_PUBLISH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'announcement.publish\'',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 185,
            'startFilePos' => 1676,
            'endTokenPos' => 185,
            'endFilePos' => 1697,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 70,
      ),
      'ACTION_ANNOUNCEMENT_ARCHIVE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTION_ANNOUNCEMENT_ARCHIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'announcement.archive\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 196,
            'startFilePos' => 1748,
            'endTokenPos' => 196,
            'endFilePos' => 1769,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 70,
      ),
      'ACTIONS' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'ACTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::ACTION_USER_CREATE, self::ACTION_USER_UPDATE, self::ACTION_ROLE_CHANGE, self::ACTION_STATUS_CHANGE, self::ACTION_COURSE_UPDATE, self::ACTION_COURSE_STATUS_CHANGE, self::ACTION_SECTION_UPDATE, self::ACTION_MISSION_UPDATE, self::ACTION_ASSESSMENT_UPDATE, self::ACTION_ASSESSMENT_STATUS_CHANGE, self::ACTION_ANNOUNCEMENT_CREATE, self::ACTION_ANNOUNCEMENT_UPDATE, self::ACTION_ANNOUNCEMENT_PUBLISH, self::ACTION_ANNOUNCEMENT_ARCHIVE]',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 71,
            'startTokenPos' => 209,
            'startFilePos' => 1919,
            'endTokenPos' => 281,
            'endFilePos' => 2473,
          ),
        ),
        'docComment' => '/**
 * Every action the trail can produce, in display order, for the server-side
 * action filter.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'FEED_PER_PAGE' => 
      array (
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'name' => 'FEED_PER_PAGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '30',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 292,
            'startFilePos' => 2510,
            'endTokenPos' => 292,
            'endFilePos' => 2511,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'recent' => 
      array (
        'name' => 'recent',
        'parameters' => 
        array (
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 78,
                'endLine' => 78,
                'startTokenPos' => 309,
                'startFilePos' => 2614,
                'endTokenPos' => 309,
                'endFilePos' => 2615,
              ),
            ),
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 28,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * @return Collection<int, AdminAudit>
 */',
        'startLine' => 78,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'currentClassName' => 'App\\Services\\AdminAuditService',
        'aliasName' => NULL,
      ),
      'feed' => 
      array (
        'name' => 'feed',
        'parameters' => 
        array (
          'actorId' => 
          array (
            'name' => 'actorId',
            'default' => NULL,
            'type' => 
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => 
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
                      'name' => 'Carbon\\Carbon',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => 
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
                      'name' => 'Carbon\\Carbon',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'paginatorQuery' => 
          array (
            'name' => 'paginatorQuery',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Pagination\\LengthAwarePaginator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The full, filterable audit trail for /admin/activity (US-709, §27.0):
 * who acted (admin_user_id), on what action, with which result, inside an
 * optional date window. Every filter is pushed into SQL — the append-only
 * table is indexed on admin_user_id, action, created_at, and
 * (target_type, target_id). Rows are newest-first; a null filter narrows
 * nothing.
 *
 * @param  array<string, string>  $paginatorQuery  validated filters preserved
 *                                                 across pagination
 * @return LengthAwarePaginator<int, AdminAudit>
 */',
        'startLine' => 99,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'currentClassName' => 'App\\Services\\AdminAuditService',
        'aliasName' => NULL,
      ),
      'actorOptions' => 
      array (
        'name' => 'actorOptions',
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
 * Every admin account, ordered for the actor filter dropdown.
 *
 * @return Collection<int, User>
 */',
        'startLine' => 151,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'currentClassName' => 'App\\Services\\AdminAuditService',
        'aliasName' => NULL,
      ),
      'record' => 
      array (
        'name' => 'record',
        'parameters' => 
        array (
          'admin' => 
          array (
            'name' => 'admin',
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
            'startLine' => 166,
            'endLine' => 166,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 9,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'summary' => 
          array (
            'name' => 'summary',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => 
            array (
              'code' => '\'success\'',
              'attributes' => 
              array (
                'startLine' => 169,
                'endLine' => 169,
                'startTokenPos' => 745,
                'startFilePos' => 5515,
                'endTokenPos' => 745,
                'endFilePos' => 5523,
              ),
            ),
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 9,
            'endColumn' => 34,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'targetType' => 
          array (
            'name' => 'targetType',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 170,
                'endLine' => 170,
                'startTokenPos' => 755,
                'startFilePos' => 5556,
                'endTokenPos' => 755,
                'endFilePos' => 5559,
              ),
            ),
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 9,
            'endColumn' => 34,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 171,
                'endLine' => 171,
                'startTokenPos' => 765,
                'startFilePos' => 5587,
                'endTokenPos' => 765,
                'endFilePos' => 5590,
              ),
            ),
            'type' => 
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\AdminAudit',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * One append-only row per administrative action. The actor\'s username is
 * captured at the time of the action (a later rename does not rewrite
 * history); created_at is the table\'s useCurrent timestamp and is never
 * caller-supplied.
 */',
        'startLine' => 165,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AdminAuditService',
        'implementingClassName' => 'App\\Services\\AdminAuditService',
        'currentClassName' => 'App\\Services\\AdminAuditService',
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