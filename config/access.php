<?php

declare(strict_types=1);

return [

    'middleware_alias' => 'permission',

    'gate' => true,

    'routes' => true,

    'route_prefix' => 'access',

    'admin_permission' => 'access.roles.manage',

    'table_prefix' => 'access_',

];
