<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\Operation;

return [

	'api:pages:is-navigation-excluded:set' => [

		'pattern' => '/api/pages/<' . Operation::KEY . ':\d+>/is-navigation-excluded',
		'controller' => __NAMESPACE__ . '\NavigationExcludeOperation',
		'via' => 'PUT'

	],

	'api:pages:is-navigation-excluded:unset' => [

		'pattern' => '/api/pages/<' . Operation::KEY . ':\d+>/is-navigation-excluded',
		'controller' => __NAMESPACE__ . '\NavigationIncludeOperation',
		'via' => 'DELETE'

	],

	'redirect:admin/site' => [

		'pattern' => '/admin/site',
		'location' => '/admin/pages'

	],

	'admin:pages/export' => [

		'pattern' => '/admin/pages/export',
		'title' => 'Export',
		'block' => 'export'

	]

];
