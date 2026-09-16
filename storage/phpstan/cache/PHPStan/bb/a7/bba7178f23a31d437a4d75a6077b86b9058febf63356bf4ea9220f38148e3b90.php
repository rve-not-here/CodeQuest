<?php declare(strict_types = 1);

// osfsl-/home/notdotguy/codequest/app/Services/XpService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\XpService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-9f4e45b9144a22d0047439c6f91949b92f4804cec01f9363cd1254d1ee88c385-8.5.10-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\XpService',
        'filename' => '/home/notdotguy/codequest/app/Services/XpService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\XpService',
    'shortName' => 'XpService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Server-authoritative XP ledger.
 *
 * Every XP change is a row in the transaction table. The balance is the sum
 * of all rows and never falls below zero. Costs for hints and the solution
 * reveal are configurable per mission-triggered action; the amount of the
 * transaction is decided here, never by the client.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 442,
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
      'TYPE_MISSION_COMPLETED' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_MISSION_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mission_completed\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 63,
            'startFilePos' => 641,
            'endTokenPos' => 63,
            'endFilePos' => 659,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'TYPE_ASSESSMENT_COMPLETED' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_ASSESSMENT_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assessment_completed\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 74,
            'startFilePos' => 708,
            'endTokenPos' => 74,
            'endFilePos' => 729,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
      'TYPE_WRONG_SUBMISSION' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_WRONG_SUBMISSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'wrong_submission\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 85,
            'startFilePos' => 774,
            'endTokenPos' => 85,
            'endFilePos' => 791,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'TYPE_HINT_USED' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_HINT_USED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'hint_used\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 96,
            'startFilePos' => 829,
            'endTokenPos' => 96,
            'endFilePos' => 839,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'TYPE_SOLUTION_REVEALED' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_SOLUTION_REVEALED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'solution_revealed\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 107,
            'startFilePos' => 885,
            'endTokenPos' => 107,
            'endFilePos' => 903,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
      'ASSESSMENT_PASSED_AMOUNT' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'ASSESSMENT_PASSED_AMOUNT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 120,
            'startFilePos' => 1177,
            'endTokenPos' => 120,
            'endFilePos' => 1179,
          ),
        ),
        'docComment' => '/**
 * XP awarded for passing a course\'s Boss Challenge (US-412). A product
 * decision, deliberately a single named constant rather than a per-row
 * value: every course\'s challenge is worth the same.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'HINT_COSTS' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'HINT_COSTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[1 => 5, 2 => 10, 3 => 15]',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 133,
            'startFilePos' => 1397,
            'endTokenPos' => 153,
            'endFilePos' => 1422,
          ),
        ),
        'docComment' => '/**
 * The XP cost of progressive hints, by hint ordinal (1-indexed).
 * The cost of revealing the full solution.
 *
 * @var array{1: int, 2: int, 3: int}
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'REVEAL_COST' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'REVEAL_COST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '30',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 164,
            'startFilePos' => 1458,
            'endTokenPos' => 164,
            'endFilePos' => 1459,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'WRONG_SUBMISSION_COST' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'WRONG_SUBMISSION_COST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 175,
            'startFilePos' => 1505,
            'endTokenPos' => 175,
            'endFilePos' => 1506,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'DEBIT_TYPES' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'DEBIT_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_WRONG_SUBMISSION, self::TYPE_HINT_USED, self::TYPE_SOLUTION_REVEALED]',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 62,
            'startTokenPos' => 188,
            'startFilePos' => 1777,
            'endTokenPos' => 205,
            'endFilePos' => 1888,
          ),
        ),
        'docComment' => '/**
 * Ledger types that spend XP. Direction on a row is the action\'s intent,
 * not the sign of the amount: a wrong-submission penalty clamped to a
 * 0-amount write (balance already at zero) is still a debit.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'AWARD_TYPES' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'AWARD_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_MISSION_COMPLETED, self::TYPE_ASSESSMENT_COMPLETED]',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 70,
            'startTokenPos' => 218,
            'startFilePos' => 2011,
            'endTokenPos' => 230,
            'endFilePos' => 2096,
          ),
        ),
        'docComment' => '/**
 * Ledger types that grant XP (mission and Boss Challenge credits).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SPEND_TYPES' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'SPEND_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_HINT_USED, self::TYPE_SOLUTION_REVEALED]',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 78,
            'startTokenPos' => 243,
            'startFilePos' => 2227,
            'endTokenPos' => 255,
            'endFilePos' => 2301,
          ),
        ),
        'docComment' => '/**
 * Ledger types the student chose to spend XP on (hints, solution reveals).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'DEDUCTION_TYPES' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'DEDUCTION_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_WRONG_SUBMISSION]',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 86,
            'startTokenPos' => 268,
            'startFilePos' => 2498,
            'endTokenPos' => 275,
            'endFilePos' => 2541,
          ),
        ),
        'docComment' => '/**
 * Ledger types that deduct XP as a penalty (wrong submissions). Together
 * SPEND_TYPES and DEDUCTION_TYPES are exactly DEBIT_TYPES.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'TYPE_ORDER' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_ORDER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_MISSION_COMPLETED, self::TYPE_ASSESSMENT_COMPLETED, self::TYPE_HINT_USED, self::TYPE_SOLUTION_REVEALED, self::TYPE_WRONG_SUBMISSION]',
          'attributes' => 
          array (
            'startLine' => 91,
            'endLine' => 97,
            'startTokenPos' => 288,
            'startFilePos' => 2653,
            'endTokenPos' => 315,
            'endFilePos' => 2843,
          ),
        ),
        'docComment' => '/**
 * Display order for the fleet-level per-type breakdown.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'TYPE_LABELS' => 
      array (
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'name' => 'TYPE_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::TYPE_MISSION_COMPLETED => \'Mission completions\', self::TYPE_ASSESSMENT_COMPLETED => \'Boss Challenge passes\', self::TYPE_HINT_USED => \'Hints purchased\', self::TYPE_SOLUTION_REVEALED => \'Solutions revealed\', self::TYPE_WRONG_SUBMISSION => \'Wrong submissions\']',
          'attributes' => 
          array (
            'startLine' => 102,
            'endLine' => 108,
            'startTokenPos' => 328,
            'startFilePos' => 2929,
            'endTokenPos' => 375,
            'endFilePos' => 3239,
          ),
        ),
        'docComment' => '/**
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'balance' => 
      array (
        'name' => 'balance',
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
            'startLine' => 110,
            'endLine' => 110,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 110,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'awardCompletion' => 
      array (
        'name' => 'awardCompletion',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 37,
            'endColumn' => 46,
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 49,
            'endColumn' => 64,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Persists XP for a successfully completed mission. Idempotent: completing
 * a mission already completed by this user does not double-award.
 */',
        'startLine' => 119,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'awardAssessmentPass' => 
      array (
        'name' => 'awardAssessmentPass',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 41,
            'endColumn' => 50,
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 53,
            'endColumn' => 74,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Records the one-time Boss Challenge reward (US-412).
 *
 * Exactly-once per course is guaranteed two ways. The primary guard lives
 * in AssessmentService::evaluateAttempt(), which calls this only when the
 * evaluation produced the student\'s FIRST pass (the history transition
 * rule); and this method is idempotent at the ledger, short-circuiting on
 * an existing assessment_completed row for this user/assessment. A later
 * retry, pass or fail, therefore never re-awards and never reverses the
 * original award.
 */',
        'startLine' => 148,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'deductWrongSubmission' => 
      array (
        'name' => 'deductWrongSubmission',
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 43,
            'endColumn' => 52,
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 55,
            'endColumn' => 70,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Deducts the wrong-submission penalty. The balance can dip below zero in
 * the transaction, but the recorded amount is clamped so the ledger never
 * sends the running total negative. Clamping is atomic in the transaction.
 */',
        'startLine' => 171,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'spendHint' => 
      array (
        'name' => 'spendHint',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 31,
            'endColumn' => 40,
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 43,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'hintNumber' => 
          array (
            'name' => 'hintNumber',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 61,
            'endColumn' => 75,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Deducts the cost of revealing the nth hint. Returns false when the
 * student cannot afford it, leaving the ledger untouched.
 */',
        'startLine' => 183,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'spendSolutionReveal' => 
      array (
        'name' => 'spendSolutionReveal',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 41,
            'endColumn' => 50,
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 53,
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
 * Deducts the cost of revealing the full solution. Returns false when the
 * student cannot afford it.
 */',
        'startLine' => 195,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'hintCost' => 
      array (
        'name' => 'hintCost',
        'parameters' => 
        array (
          'hintNumber' => 
          array (
            'name' => 'hintNumber',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 30,
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
        'docComment' => NULL,
        'startLine' => 201,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'revealCost' => 
      array (
        'name' => 'revealCost',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 206,
        'endLine' => 209,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'assessmentPassedAmount' => 
      array (
        'name' => 'assessmentPassedAmount',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 211,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'wrongSubmissionCost' => 
      array (
        'name' => 'wrongSubmissionCost',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 216,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'canAfford' => 
      array (
        'name' => 'canAfford',
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
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cost' => 
          array (
            'name' => 'cost',
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
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 43,
            'endColumn' => 51,
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
        'docComment' => NULL,
        'startLine' => 221,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'revealedHintCount' => 
      array (
        'name' => 'revealedHintCount',
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
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 39,
            'endColumn' => 48,
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
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 51,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * How many hints a user has already paid to reveal on a mission.
 */',
        'startLine' => 229,
        'endLine' => 236,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'hasRevealedSolution' => 
      array (
        'name' => 'hasRevealedSolution',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 41,
            'endColumn' => 50,
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 53,
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
 * Whether the user has paid to reveal the full solution on a mission.
 */',
        'startLine' => 241,
        'endLine' => 248,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'transactionHistory' => 
      array (
        'name' => 'transactionHistory',
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
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 40,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '30',
              'attributes' => 
              array (
                'startLine' => 264,
                'endLine' => 264,
                'startTokenPos' => 1222,
                'startFilePos' => 8706,
                'endTokenPos' => 1222,
                'endFilePos' => 8707,
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
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 52,
            'endColumn' => 66,
            'parameterIndex' => 1,
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
 * The student\'s recent XP ledger history, newest first, as display rows.
 * The balance shown beside this list must come from balance(); nothing
 * here recomputes a second authoritative total (§18.0/§20.0). Rows expose
 * no internal database IDs, validation rules, or grading logic.
 *
 * @return Collection<int, array{
 *     amount: int,
 *     direction: \'credit\'|\'debit\',
 *     reason: string,
 *     source: \'mission\'|\'assessment\',
 *     at: Carbon,
 * }>
 */',
        'startLine' => 264,
        'endLine' => 273,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'fleetSummary' => 
      array (
        'name' => 'fleetSummary',
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
 * Fleet-wide XP administration statistics (US-710, §30.0). A read-only
 * aggregate over the ledger, never a per-student fan-out: one grouped
 * query classifies every row by its TYPE (the action\'s intent) — awarded =
 * mission + assessment credits, spent = hints + solution reveals, deducted
 * = wrong-submission penalties. Outstanding is the net ledger sum
 * (awarded − spent − deducted). Amounts for spend/deduct groups use the
 * absolute value, so a penalty clamped to a 0-amount row still counts
 * with the direction its type declares.
 *
 * @return array{
 *     awarded: int,
 *     spent: int,
 *     deducted: int,
 *     outstanding: int,
 *     accounts: int,
 *     by_type: list<array{
 *         type: string,
 *         label: string,
 *         direction: \'award\'|\'spend\'|\'deduct\',
 *         entries: int,
 *         total: int,
 *     }>,
 * }
 */',
        'startLine' => 300,
        'endLine' => 355,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'presentTransaction' => 
      array (
        'name' => 'presentTransaction',
        'parameters' => 
        array (
          'txn' => 
          array (
            'name' => 'txn',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\XpTransaction',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 366,
            'endLine' => 366,
            'startColumn' => 41,
            'endColumn' => 58,
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
 * @return array{
 *     amount: int,
 *     direction: \'credit\'|\'debit\',
 *     reason: string,
 *     source: \'mission\'|\'assessment\',
 *     at: Carbon,
 * }
 */',
        'startLine' => 366,
        'endLine' => 375,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'spend' => 
      array (
        'name' => 'spend',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 28,
            'endColumn' => 37,
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'cost' => 
          array (
            'name' => 'cost',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 58,
            'endColumn' => 66,
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 69,
            'endColumn' => 80,
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 83,
            'endColumn' => 101,
            'parameterIndex' => 4,
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
 * General spend: deducts cost if affordable, else returns false.
 */',
        'startLine' => 380,
        'endLine' => 391,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'spendFrom' => 
      array (
        'name' => 'spendFrom',
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 32,
            'endColumn' => 41,
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 44,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'cost' => 
          array (
            'name' => 'cost',
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 62,
            'endColumn' => 70,
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 73,
            'endColumn' => 84,
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 87,
            'endColumn' => 105,
            'parameterIndex' => 4,
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
 * Records a negative transaction clamped against the current balance so
 * the running total never goes below zero.
 */',
        'startLine' => 397,
        'endLine' => 412,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'record' => 
      array (
        'name' => 'record',
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 29,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mission' => 
          array (
            'name' => 'mission',
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
                      'name' => 'App\\Models\\Mission',
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 41,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'assessment' => 
          array (
            'name' => 'assessment',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 60,
            'endColumn' => 82,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'amount' => 
          array (
            'name' => 'amount',
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 85,
            'endColumn' => 95,
            'parameterIndex' => 3,
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 98,
            'endColumn' => 109,
            'parameterIndex' => 4,
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 112,
            'endColumn' => 130,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Writes a ledger row. Exactly one of the two source references must be
 * set: the populated FK identifies the source (§25). There is no
 * source_type column; mission transactions carry mission_id (assessment_id
 * null), assessment transactions the mirror image.
 */',
        'startLine' => 420,
        'endLine' => 434,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
        'aliasName' => NULL,
      ),
      'sumFor' => 
      array (
        'name' => 'sumFor',
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
            'startLine' => 436,
            'endLine' => 436,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 436,
        'endLine' => 441,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\XpService',
        'implementingClassName' => 'App\\Services\\XpService',
        'currentClassName' => 'App\\Services\\XpService',
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