<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/NotificationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\NotificationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-e19b32a0300ec5ce728e5f368e0c28a47be99afbd3336f1517351a3db3d52c0c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\NotificationService',
        'filename' => '/home/notdotguy/codequest/app/Services/NotificationService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\NotificationService',
    'shortName' => 'NotificationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The one notification write+read service (US-801..US-806). Every method is
 * keyed by the authenticated user; no method accepts a target-user
 * identifier, so no route or parameter can retrieve another user\'s
 * notification. Reads are scoped in SQL by user_id, and the
 * single-notification accessor (findForUser) resolves only rows already
 * owned — a foreign or nonexistent id is indistinguishable (null), never a
 * leak. Marking is reuse of the same primitive: markAsRead resolves via
 * findForUser, so a foreign, nonexistent, or already-read row is an
 * identical idempotent no-op.
 *
 * Producers (US-804/805): create() is the only row writer and is called from
 * inside the same DB::transaction as the underlying state change (§19). The
 * data payload is always a route NAME plus params — never a URL — and
 * create() only accepts the exact route a type\'s TYPE_ROUTES entry allows,
 * so a raw or client-influenced destination cannot be stored (§27/§42).
 * Since US-806 create() also preserves server-authored auxiliary data keys
 * (e.g. the attention signals a teacher_attention row was emitted for) so a
 * producer can store its change-detection state next to the safe link.
 * linkFor() is the one renderer and re-applies the same allowlist before
 * emitting an href, returning null on any mismatch.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 347,
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
      'FEED_PER_PAGE' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'FEED_PER_PAGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '30',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 55,
            'startFilePos' => 1714,
            'endTokenPos' => 55,
            'endFilePos' => 1715,
          ),
        ),
        'docComment' => '/** The center\'s page size, matching the other feed services (30). */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'TYPE_MISSION_COMPLETED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_MISSION_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mission_completed\'',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 66,
            'startFilePos' => 1761,
            'endTokenPos' => 66,
            'endFilePos' => 1779,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_ASSESSMENT_UNLOCKED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_ASSESSMENT_UNLOCKED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment_unlocked\'',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 77,
            'startFilePos' => 1827,
            'endTokenPos' => 77,
            'endFilePos' => 1847,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 66,
      ),
      'TYPE_ASSESSMENT_PASSED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_ASSESSMENT_PASSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment_passed\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 88,
            'startFilePos' => 1893,
            'endTokenPos' => 88,
            'endFilePos' => 1911,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_ASSESSMENT_FAILED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_ASSESSMENT_FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment_failed\'',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 99,
            'startFilePos' => 1957,
            'endTokenPos' => 99,
            'endFilePos' => 1975,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_COURSE_COMPLETED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_COURSE_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'course_completed\'',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 110,
            'startFilePos' => 2020,
            'endTokenPos' => 110,
            'endFilePos' => 2037,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'TYPE_NEXT_COURSE_UNLOCKED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_NEXT_COURSE_UNLOCKED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'next_course_unlocked\'',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 121,
            'startFilePos' => 2086,
            'endTokenPos' => 121,
            'endFilePos' => 2107,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
      'TYPE_ACHIEVEMENT_EARNED' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_ACHIEVEMENT_EARNED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'achievement_earned\'',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 132,
            'startFilePos' => 2154,
            'endTokenPos' => 132,
            'endFilePos' => 2173,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
      'TYPE_TEACHER_ATTENTION' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_TEACHER_ATTENTION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'teacher_attention\'',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 143,
            'startFilePos' => 2219,
            'endTokenPos' => 143,
            'endFilePos' => 2237,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_SYSTEM_ANNOUNCEMENT' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_SYSTEM_ANNOUNCEMENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'system_announcement\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 154,
            'startFilePos' => 2285,
            'endTokenPos' => 154,
            'endFilePos' => 2305,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 66,
      ),
      'TYPE_DRAFT_REMINDER' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_DRAFT_REMINDER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'draft_reminder\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 165,
            'startFilePos' => 2348,
            'endTokenPos' => 165,
            'endFilePos' => 2363,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
      'TYPE_LEARNING_REMINDER' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_LEARNING_REMINDER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'learning_reminder\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 176,
            'startFilePos' => 2409,
            'endTokenPos' => 176,
            'endFilePos' => 2427,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_ROUTES' => 
      array (
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'name' => 'TYPE_ROUTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    self::TYPE_MISSION_COMPLETED => \'mission.show\',
    self::TYPE_ASSESSMENT_UNLOCKED => \'assessment.show\',
    self::TYPE_ASSESSMENT_PASSED => \'assessment.show\',
    self::TYPE_ASSESSMENT_FAILED => \'assessment.show\',
    self::TYPE_COURSE_COMPLETED => \'learning-path\',
    self::TYPE_NEXT_COURSE_UNLOCKED => \'learning-path\',
    self::TYPE_ACHIEVEMENT_EARNED => \'achievements\',
    self::TYPE_TEACHER_ATTENTION => \'needs-attention\',
    // A system announcement IS delivered through the notification center
    // itself (US-807, §9): the row announces itself, so the safe inbound
    // link is the center route rather than a raw destination.
    self::TYPE_SYSTEM_ANNOUNCEMENT => \'notifications\',
    // Student reminders (US-809): a stale draft points back at the mission
    // it belongs to; a course-level reminder opens the learning path.
    self::TYPE_DRAFT_REMINDER => \'mission.show\',
    self::TYPE_LEARNING_REMINDER => \'learning-path\',
]',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 85,
            'startTokenPos' => 189,
            'startFilePos' => 2733,
            'endTokenPos' => 300,
            'endFilePos' => 3754,
          ),
        ),
        'docComment' => '/**
 * The only link a notification of a given type may target. A producer
 * must register its destination here before it can be produced; the map
 * is the allowlist both create() and linkFor() enforce.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'belongsTo' => 
      array (
        'name' => 'belongsTo',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'notification' => 
          array (
            'name' => 'notification',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Notification',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 43,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Ownership predicate, the same explicit check AssessmentService exposes
 * as hasAccessToAttempt. A notification belongs to exactly one user.
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'forUser' => 
      array (
        'name' => 'forUser',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 29,
            'endColumn' => 38,
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
            'name' => 'Illuminate\\Pagination\\LengthAwarePaginator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The paginated notification center feed, newest first (§12/§44). Scoped
 * in SQL by user_id; nothing here accepts a user id parameter. Pagination
 * mirrors the other feed services (LengthAwarePaginator, in-memory page
 * slice of the caller\'s own rows).
 *
 * @return LengthAwarePaginator<int, Notification>
 */',
        'startLine' => 104,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'unreadCount' => 
      array (
        'name' => 'unreadCount',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 33,
            'endColumn' => 42,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Unread count (§13/§44) as a SQL COUNT over the (user_id, read_at)
 * index — never a collection filtered in PHP. The read-state filter is
 * pushed into the query, so the composite index covers the scan.
 */',
        'startLine' => 128,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'markAsRead' => 
      array (
        'name' => 'markAsRead',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'notificationId' => 
          array (
            'name' => 'notificationId',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 44,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Mark one notification read (§14/§42). Resolves through findForUser, so
 * ownership is enforced by the same primitive as the read path and a
 * foreign or nonexistent id is an identical safe no-op — never a 404/403
 * distinction that would leak existence. Idempotent: an already-read row
 * returns false and never rewrites read_at.
 */',
        'startLine' => 143,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'markAllAsRead' => 
      array (
        'name' => 'markAllAsRead',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 35,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Mark every unread notification read (§14/§42). Scoped by user_id, so a
 * crafted request can never touch another user\'s rows; idempotent — a
 * second call updates zero rows. Returns the number of rows changed.
 */',
        'startLine' => 162,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'findForUser' => 
      array (
        'name' => 'findForUser',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'notificationId' => 
          array (
            'name' => 'notificationId',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 45,
            'endColumn' => 63,
            'parameterIndex' => 1,
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
                  'name' => 'App\\Models\\Notification',
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
 * Ownership-gated single-notification lookup. The id resolves only when
 * the row belongs to the caller; a foreign or nonexistent id returns
 * null, so callers cannot distinguish "not yours" from "doesn\'t exist".
 */',
        'startLine' => 175,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'payload' => 
      array (
        'name' => 'payload',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 36,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 749,
                'startFilePos' => 7816,
                'endTokenPos' => 750,
                'endFilePos' => 7817,
              ),
            ),
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 51,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'auxiliary' => 
          array (
            'name' => 'auxiliary',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 759,
                'startFilePos' => 7839,
                'endTokenPos' => 760,
                'endFilePos' => 7840,
              ),
            ),
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 71,
            'endColumn' => 91,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
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
 * Server-authored destination payload. Only route NAMES are ever passed
 * in — a raw URL cannot be stored because create() only accepts routes
 * on the type\'s allowlist (§27/§42). Since US-806, server-authored
 * auxiliary keys (e.g. the attention signals a row was emitted for) pass
 * through $auxiliary unchanged; only trusted producers construct
 * payloads, and linkFor() reads the route/params pair alone.
 *
 * @param  array<int|string, mixed>  $params
 * @param  array<array-key, mixed>  $auxiliary
 * @return array{route: string, params: array<int|string, mixed>}&array<array-key, mixed>
 */',
        'startLine' => 195,
        'endLine' => 198,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 0,
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
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 9,
            'endColumn' => 20,
            'parameterIndex' => 1,
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
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 9,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'dedupeKey' => 
          array (
            'name' => 'dedupeKey',
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
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 9,
            'endColumn' => 19,
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
                  'name' => 'App\\Models\\Notification',
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
 * The sole notification write path. Participates in the caller\'s existing
 * transaction — never opens its own — so an event rollback silently
 * removes the notification too (§19). The dedupe pre-check runs inside
 * the caller\'s transaction as well, so a concurrent duplicate commit
 * cannot race past it; the unique (user_id, dedupe_key) DB index is the
 * backstop for any remaining edge case (§17).
 *
 * Returns null when the dedupe key already exists (idempotent no-op);
 * the producer simply discards the null, just like
 * AchievementService::award discards false.
 *
 * @param  array<array-key, mixed>  $data
 */',
        'startLine' => 214,
        'endLine' => 268,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'linkFor' => 
      array (
        'name' => 'linkFor',
        'parameters' => 
        array (
          'notification' => 
          array (
            'name' => 'notification',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Notification',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 277,
            'endLine' => 277,
            'startColumn' => 29,
            'endColumn' => 54,
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
 * Safe href for a server-produced notification. Re-applies the same
 * TYPE_ROUTES allowlist and Route::has check that create() enforced, so
 * a row tampered with at the database layer still cannot emit a raw URL
 * or link to an unintended internal target — the method simply returns
 * null and the view renders no link.
 */',
        'startLine' => 277,
        'endLine' => 307,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'alreadyProduced' => 
      array (
        'name' => 'alreadyProduced',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
            'startLine' => 315,
            'endLine' => 315,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dedupeKey' => 
          array (
            'name' => 'dedupeKey',
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
            'startLine' => 315,
            'endLine' => 315,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Whether the user already received an event with this dedupe key.
 * Runs inside the caller\'s transaction so a concurrent duplicate commit
 * cannot race past this check; the unique (user_id, dedupe_key) DB
 * index is the backstop.
 */',
        'startLine' => 315,
        'endLine' => 321,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
        'aliasName' => NULL,
      ),
      'normalizeParams' => 
      array (
        'name' => 'normalizeParams',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 331,
            'endLine' => 331,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ensure every param is a scalar value coercible to int — reject nested
 * arrays, objects, or non-numeric strings. This prevents a malformed
 * payload from passing through to route() unchanged.
 *
 * @param  array<int|string, mixed>  $params
 * @return array<int|string, int>
 */',
        'startLine' => 331,
        'endLine' => 346,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\NotificationService',
        'implementingClassName' => 'App\\Services\\NotificationService',
        'currentClassName' => 'App\\Services\\NotificationService',
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