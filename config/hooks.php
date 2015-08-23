<?php

namespace Icybee\Modules\Pages;

$hooks = Hooks::class . '::';

use ICanBoogie\HTTP\RequestDispatcher;
use Icybee\Modules;

return [

	'patron.markups' => [

		'page:content' => [

			$hooks . 'markup_page_content', [

				'id' => [ 'required' => true ],
				'title' => [ 'required' => true ],
				'editor' => null,
				'render' => [ 'required' => true, 'default' => 'auto' ],
				'no-wrapper' => false

			]
		],

		'page:languages' => [

			LanguagesElement::class . '::markup', [

			]
		],

		'navigation' => [

			$hooks . 'markup_navigation', [

				'css-class-names' => '-constructor -slug -template',
				'depth' => 2,
				'from-level' => 0,
				'min-children' => 0,
				'parent' => 0

			]
		],

		'navigation:leaf' => [

			NavigationBranchElement::class . '::markup_navigation_leaf', [

				'css-class-names' => 'active trail id',
				'depth' => 2

			]
		],

		'breadcrumb' => [

			BreadcrumbElement::class . '::markup', [

				'page' => [ 'expression' => true, 'required' => true, 'default' => 'this' ]

			]
		],

		#
		# cache
		#

		'page:region' => [

			$hooks . 'markup_page_region', [

				'id' => [ 'required' => true ]

			]
		],

		'page:title' => [

			$hooks . 'markup_page_title', [


			]
		]
	]
];
