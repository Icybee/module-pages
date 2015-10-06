<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;
use Icybee\Routing\RouteMaker as Make;

return [

	'api:pages:is-navigation-excluded:set' => [

		'pattern' => '/api/pages/<' . Operation::KEY . ':\d+>/is-navigation-excluded',
		'controller' => NavigationExcludeOperation::class,
		'via' => 'PUT'

	],

	'api:pages:is-navigation-excluded:unset' => [

		'pattern' => '/api/pages/<' . Operation::KEY . ':\d+>/is-navigation-excluded',
		'controller' => NavigationIncludeOperation::class,
		'via' => 'DELETE'

	],

	'redirect:admin/site' => [

		'pattern' => '/admin/site',
		'location' => '/admin/pages'

	]

] + Make::admin('pages', Routing\PagesAdminController::class, [

	'id_name' => 'nid',
	'only' => [ Make::ACTION_INDEX, Make::ACTION_NEW, Make::ACTION_EDIT, Make::ACTION_CONFIRM_DELETE, 'export' ],
	'actions' => [

		'export' => [ '/{name}/export', Request::METHOD_ANY ]

	]

]);
