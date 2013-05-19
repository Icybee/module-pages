<?php

namespace Icybee\Modules\Pages;

return array
(
	'api:pages:is-navigation-excluded:set' => array
	(
		'pattern' => '/api/pages/<_operation_key:\d+>/is-navigation-excluded',
		'controller' => __NAMESPACE__ . '\NavigationExcludeOperation',
		'via' => 'PUT'
	),

	'api:pages:is-navigation-excluded:unset' => array
	(
		'pattern' => '/api/pages/<_operation_key:\d+>/is-navigation-excluded',
		'controller' => __NAMESPACE__ . '\NavigationIncludeOperation',
		'via' => 'DELETE'
	),

	'redirect:admin/site' => array
	(
		'pattern' => '/admin/site',
		'location' => '/admin/pages'
	),

	'admin:pages/export' => array
	(
		'pattern' => '/admin/pages/export',
		'title' => 'Export',
		'block' => 'export'
	)
);