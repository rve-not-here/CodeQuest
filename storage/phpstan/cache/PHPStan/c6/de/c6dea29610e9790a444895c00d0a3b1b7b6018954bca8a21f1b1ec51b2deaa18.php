<?php declare(strict_types = 1);

// odsl-/home/notdotguy/codequest/app/Http/Requests/AdminAnnouncementStoreRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Requests\AdminAnnouncementStoreRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.5.10-295f0e24108fc26eb6ce8401b1a50c3213f04d4371eb4027fd176e0592ea5f6d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
        'filename' => '/home/notdotguy/codequest/app/Http/Requests/AdminAnnouncementStoreRequest.php',
      ),
    ),
    'namespace' => 'App\\Http\\Requests',
    'name' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
    'shortName' => 'AdminAnnouncementStoreRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Announcement creation payload (US-807, §30.0–§33.0). Only title, message,
 * and audience are accepted; status is always server-set to \'draft\' on
 * creation (a crafted payload can never skip straight to published/archived),
 * and audience is validated against the exact AnnouncementService::AUDIENCES
 * set — the reach of an announcement is server-determined, never free-form.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 34,
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
        'startLine' => 18,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
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
        'startLine' => 26,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Requests',
        'declaringClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
        'implementingClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
        'currentClassName' => 'App\\Http\\Requests\\AdminAnnouncementStoreRequest',
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