<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\Facets\Criterion\BooleanCriterion;

return [

	'facets' => [

		'pages' => [

			'is_navigation_excluded' => BooleanCriterion::class

		]

	]

];
