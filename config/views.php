<?php

namespace Icybee\Modules\Pages;

use Icybee\Modules\Views\ViewOptions as Options;

return [

	'pages' => [

		'list' => [

			Options::TITLE => 'Sitemap',
			Options::CLASSNAME => ListView::class,
			Options::RENDERS => Options::RENDERS_MANY

		]

	]

];
