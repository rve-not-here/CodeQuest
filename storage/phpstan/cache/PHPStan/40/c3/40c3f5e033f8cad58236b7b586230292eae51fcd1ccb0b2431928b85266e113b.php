<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Services/RecommendationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\RecommendationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-1ca93eeca0060a01b61f567ba2107c4f05cbb0a6a61263e0530cf15a9d84404a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\RecommendationService',
        'filename' => '/home/notdotguy/codequest/app/Services/RecommendationService.php',
      ),
    ),
    'namespace' => 'App\\Services',
    'name' => 'App\\Services\\RecommendationService',
    'shortName' => 'RecommendationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Personalized recommendations (US-509). Deterministic and non-AI: every
 * slot is derived server-side from the student\'s own real state, in the
 * §31.0 priority order:
 *
 *   1. current incomplete learning   -> the ResumeService position when a
 *      mission is unfinished (type \'mission\'); the next mission to work on.
 *   2. required next curriculum item -> the ResumeService position when every
 *      mission is done but the Boss Challenge is outstanding (type \'course\').
 *      Unlike ResumeService\'s CTA policy (US-504), this recommendation MAY
 *      deep-link assessment.show because it presents the required next action,
 *      not a resume.
 *   3. review-worthy past struggles  -> completed missions with at least
 *      REVIEW_WRONG_SUBMISSION_THRESHOLD wrong submissions in the last
 *      REVIEW_WINDOW_DAYS days. Restriction C: only missions the student has
 *      actually completed are considered, so live in-progress work never
 *      appears here (it stays in slot 1).
 *   4. next course after completion  -> the ResumeService position when the
 *      current course is untouched but an earlier active course has had its
 *      Boss Challenge passed.
 *
 * Slots 1, 2 and 4 are the three states of the SAME resume resolution, so at
 * most one of them fires; slot 3 is independent. The page therefore renders
 * at most one position card (slot 1, 2 or 4) plus up to REVIEW_LIMIT review
 * cards (slot 3). Rendering order honours §31.0 strictly: a slot-1 or slot-2
 * position renders first followed by the review cards (1/2 before 3), but the
 * review cards render before a slot-4 position (3 sorts before 4). When
 * nothing is applicable the collection is empty and the page shows the empty
 * state.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 42,
    'endLine' => 322,
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
      'REVIEW_WINDOW_DAYS' => 
      array (
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'name' => 'REVIEW_WINDOW_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 57,
            'startFilePos' => 2032,
            'endTokenPos' => 57,
            'endFilePos' => 2033,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'REVIEW_WRONG_SUBMISSION_THRESHOLD' => 
      array (
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'name' => 'REVIEW_WRONG_SUBMISSION_THRESHOLD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 68,
            'startFilePos' => 2090,
            'endTokenPos' => 68,
            'endFilePos' => 2090,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
      'REVIEW_LIMIT' => 
      array (
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'name' => 'REVIEW_LIMIT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 79,
            'startFilePos' => 2126,
            'endTokenPos' => 79,
            'endFilePos' => 2126,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
      'resume' => 
      array (
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'name' => 'resume',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\ResumeService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 9,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'assessments' => 
      array (
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'name' => 'assessments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Services\\AssessmentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 9,
        'endColumn' => 55,
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
          'resume' => 
          array (
            'name' => 'resume',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\ResumeService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 9,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'assessments' => 
          array (
            'name' => 'assessments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Services\\AssessmentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 9,
            'endColumn' => 55,
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'recommendations' => 
      array (
        'name' => 'recommendations',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 37,
            'endColumn' => 46,
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
 * @return SupportCollection<int, array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }>
 */',
        'startLine' => 64,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'positionCard' => 
      array (
        'name' => 'positionCard',
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
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 35,
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
                  'name' => 'array',
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
 * @return null|array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 89,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'challengeCard' => 
      array (
        'name' => 'challengeCard',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 36,
            'endColumn' => 49,
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
 * Slot 2: the required next curriculum item. This is a recommendation
 * pointing at the required next action, so it deep-links assessment.show;
 * it is deliberately NOT subject to ResumeService\'s no-assessment-link
 * rule (US-504). Under content drift where the course has no assessment
 * row, the card falls back to the learning surface rather than
 * mislinking a null.
 *
 * @return array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 132,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'continueLearningCard' => 
      array (
        'name' => 'continueLearningCard',
        'parameters' => 
        array (
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 43,
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
 * Slot 1: the current incomplete learning position (US-504 resolution).
 *
 * @return array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 156,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'courseCard' => 
      array (
        'name' => 'courseCard',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 33,
            'endColumn' => 46,
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
 * Slot 1, course-level fallback for the impossible-content-drift case
 * where ResumeService reports a mission but it is missing from the data.
 *
 * @return array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 179,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'nextCourseCard' => 
      array (
        'name' => 'nextCourseCard',
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 37,
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
 * Slot 4: the next course, shown only right after a completion — the
 * current course is untouched AND an earlier active course has had its
 * Boss Challenge passed. Once the student starts the next course (any
 * Progress row), slot 1 takes over the position.
 *
 * @return array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 204,
        'endLine' => 213,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'nextCourseAfterCompletion' => 
      array (
        'name' => 'nextCourseAfterCompletion',
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
            'startColumn' => 48,
            'endColumn' => 57,
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 60,
            'endColumn' => 73,
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
        'startLine' => 215,
        'endLine' => 231,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'reviewCards' => 
      array (
        'name' => 'reviewCards',
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
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 34,
            'endColumn' => 43,
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
 * Slot 3: the review queue (Option B + the confirmed restriction C).
 *
 * A mission qualifies when its wrong_submission journal rows over the
 * last REVIEW_WINDOW_DAYS days number at least REVIEW_WRONG_SUBMISSION_THRESHOLD.
 * The window is inclusive of exactly REVIEW_WINDOW_DAYS days ago
 * (created_at >= now()->subDays(REVIEW_WINDOW_DAYS)). Restriction C keeps
 * in-progress missions out of here: the count only considers missions the
 * student has a Progress row for. Cards are ordered by the most recent
 * wrong submission and capped at REVIEW_LIMIT.
 *
 * @return SupportCollection<int, array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }>
 */',
        'startLine' => 252,
        'endLine' => 286,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'reviewCard' => 
      array (
        'name' => 'reviewCard',
        'parameters' => 
        array (
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
            'startLine' => 297,
            'endLine' => 297,
            'startColumn' => 33,
            'endColumn' => 49,
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
                  'name' => 'array',
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
 * @return null|array{
 *     slot: 1|2|3|4,
 *     title: string,
 *     subtitle: string,
 *     href: string,
 *     cta: string,
 * }
 */',
        'startLine' => 297,
        'endLine' => 310,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services',
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
        'aliasName' => NULL,
      ),
      'completedMissionIds' => 
      array (
        'name' => 'completedMissionIds',
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
            'startColumn' => 42,
            'endColumn' => 51,
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
 * @return array<int, int>
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
        'declaringClassName' => 'App\\Services\\RecommendationService',
        'implementingClassName' => 'App\\Services\\RecommendationService',
        'currentClassName' => 'App\\Services\\RecommendationService',
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