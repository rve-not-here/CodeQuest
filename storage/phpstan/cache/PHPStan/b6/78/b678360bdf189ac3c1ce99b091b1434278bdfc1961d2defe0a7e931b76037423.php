<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/AnnouncementService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\AnnouncementService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-d4862680e09cfb0843d710af9ae5cfb120ae7d988e65a4c0bb94dfa14dc62eb1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\AnnouncementService',
        'filename' => '/home/notdotguy/codequest/app/Services/AnnouncementService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\AnnouncementService',
    'shortName' => 'AnnouncementService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The only write path for admin-authored announcements (US-807, §30.0–§33.0).
 * Announcements live on a draft → published → archived lifecycle; audience
 * (all|students|teachers|admins) is server-determined and decides who receives
 * a SYSTEM_ANNOUNCEMENT notification at publish. The operator role is
 * deliberately excluded from every audience.
 *
 * publish() is FIRST-PUBLISH-ONLY (§31.0): published_at is set once on the
 * first publish, fanning out one notification per matching ACTIVE user
 * (dedupe_key announcement:{id}, unique per user). A later edit of a
 * published announcement never re-notifies, archiving is silent, and
 * re-announcing means creating a new announcement. The recursion guard is the
 * state gate: only \'draft\' can be published, only \'published\' can be
 * archived, and an archived announcement is read-only.
 *
 * Every refusal records a \'failed\' audit row before the exception is thrown,
 * the same discipline as CourseService/UserService; every applied change
 * records a \'success\' row, and a no-op round-trip records nothing.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 303,
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
      'AUDIENCES' => 
      array (
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'name' => 'AUDIENCES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'all\', \'students\', \'teachers\', \'admins\']',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 50,
            'startFilePos' => 1570,
            'endTokenPos' => 61,
            'endFilePos' => 1610,
          ),
        ),
        'docComment' => '/**
 * The server-determined audience set, mirrored from the the404_announcements
 * enum column. The request validates first; the service re-validates as
 * defense-in-depth. Operator is never a deliverable audience.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 71,
      ),
      'STATUSES' => 
      array (
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'draft\', \'published\', \'archived\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 74,
            'startFilePos' => 1734,
            'endTokenPos' => 82,
            'endFilePos' => 1767,
          ),
        ),
        'docComment' => '/**
 * The publication lifecycle states (draft → published → archived).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 63,
      ),
      'AUDIENCE_ROLES' => 
      array (
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'name' => 'AUDIENCE_ROLES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'all\' => [\'student\', \'teacher\', \'admin\'], \'students\' => [\'student\'], \'teachers\' => [\'teacher\'], \'admins\' => [\'admin\']]',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 56,
            'startTokenPos' => 95,
            'startFilePos' => 2078,
            'endTokenPos' => 139,
            'endFilePos' => 2235,
          ),
        ),
        'docComment' => '/**
 * The roles each audience reaches. \'all\' spans every non-operator
 * learning/teaching role; the operator is excluded from delivery by
 * design (§9) and is not the target of any audience.
 *
 * @var array<string, array<int, string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'notifications' => 
      array (
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'name' => 'notifications',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\NotificationService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 9,
        'endColumn' => 59,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'audit' => 
      array (
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
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
        'startLine' => 60,
        'endLine' => 60,
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
          'notifications' => 
          array (
            'name' => 'notifications',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\NotificationService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 9,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 9,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
        'aliasName' => NULL,
      ),
      'index' => 
      array (
        'name' => 'index',
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
 * Every announcement, newest first, with the author eager-loaded for the
 * admin listing.
 *
 * @return Collection<int, Announcement>
 */',
        'startLine' => 69,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 41,
            'endColumn' => 57,
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
            'name' => 'App\\Models\\Announcement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create an announcement as a draft. status is set by the server, never
 * by the client — every new announcement lands in \'draft\' and only the
 * publish action moves it forward. Records an \'announcement.create\'
 * success row against the new announcement.
 *
 * @param  array{title: string, message: string, audience: string}  $attributes
 */',
        'startLine' => 86,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'announcement' => 
          array (
            'name' => 'announcement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Announcement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 41,
            'endColumn' => 66,
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 69,
            'endColumn' => 85,
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
            'name' => 'App\\Models\\Announcement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Edit a draft or published announcement\'s title/message/audience. An
 * archived announcement is read-only. Editing a published announcement
 * NEVER re-notifies (§31): the delivered rows are first-publish-only and
 * this write touches only the announcement row, never the404_notifications
 * (no retroactive rewrite of delivered messages).
 *
 * Allow-list checks and the archived guard run BEFORE any write. A no-op
 * round-trip records no audit row; refusals record a \'failed\' row first.
 *
 * @param  array{title: string, message: string, audience: string}  $attributes
 */',
        'startLine' => 119,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
        'aliasName' => NULL,
      ),
      'publish' => 
      array (
        'name' => 'publish',
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
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'announcement' => 
          array (
            'name' => 'announcement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Announcement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 42,
            'endColumn' => 67,
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
            'name' => 'App\\Models\\Announcement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Publish an announcement: first-publish-only (§31). Guards run BEFORE
 * any write (and before the transaction) so a refused row always persists
 * on the trail; the success audit row plus the notification fan-out run
 * inside ONE transaction so a rollback removes the fan-out too (§19).
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 184,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
        'aliasName' => NULL,
      ),
      'archive' => 
      array (
        'name' => 'archive',
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
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'announcement' => 
          array (
            'name' => 'announcement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Announcement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 42,
            'endColumn' => 67,
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
            'name' => 'App\\Models\\Announcement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Archive a published announcement (terminal state, §31/§32). Only a
 * published announcement can be archived; archiving is silent — it does
 * NOT notify and never touches delivered notification rows. A success
 * \'announcement.archive\' row records the transition.
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 229,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
        'aliasName' => NULL,
      ),
      'announce' => 
      array (
        'name' => 'announce',
        'parameters' => 
        array (
          'announcement' => 
          array (
            'name' => 'announcement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Announcement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 31,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fan out one SYSTEM_ANNOUNCEMENT notification per matching ACTIVE user.
 * The audience map is the single server-determined reach decision; the
 * dedupe key announcement:{id} makes the delivery idempotent per user and
 * the (user_id, dedupe_key) unique index is the backstop. Title and
 * message pass through verbatim — no reformatting vocabulary.
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 260,
        'endLine' => 279,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'announcement' => 
          array (
            'name' => 'announcement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Announcement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 42,
            'endColumn' => 67,
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 70,
            'endColumn' => 83,
            'parameterIndex' => 2,
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 86,
            'endColumn' => 99,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Record a failed announcement-mutation attempt, then throw. Consistent
 * with CourseService/UserService: the ledger carries the row before the
 * exception propagates.
 *
 * @return never
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 290,
        'endLine' => 302,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\AnnouncementService',
        'implementingClassName' => 'App\\Services\\AnnouncementService',
        'currentClassName' => 'App\\Services\\AnnouncementService',
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