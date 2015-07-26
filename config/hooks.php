<?php

namespace Icybee\Modules\Pages;

$hooks = Hooks::class . '::';

use ICanBoogie\HTTP\RequestDispatcher;
use Icybee\Modules;

return [

	'events' => [

		'Brickrouge\Document::render_title:before' => $hooks . 'before_document_render_title',
		RequestDispatcher::class . '::alter' => $hooks . 'on_http_dispatcher_alter',
		'ICanBoogie\SaveOperation::process' => $hooks . 'invalidate_cache',
		'ICanBoogie\DeleteOperation::process' => $hooks . 'invalidate_cache',
		Modules\Files\File::class . '::move' => $hooks . 'on_file_move',
		Modules\Pages\Page::class . '::move' => $hooks . 'on_page_move',
		Modules\Nodes\OnlineOperation::class . '::process' => $hooks . 'invalidate_cache',
		Modules\Nodes\OfflineOperation::class . '::process' => $hooks . 'invalidate_cache'

	],

	'prototypes' => [

		Modules\Sites\Site::class . '::lazy_get_home' => $hooks . 'get_home',
		'ICanBoogie\Core::get_page' => $hooks . 'get_page',
		Page::class . '::render' => $hooks . 'render_page'

	],

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
