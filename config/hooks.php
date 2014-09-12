<?php

namespace Icybee\Modules\Pages;

$hooks = __NAMESPACE__ . '\Hooks::';

return [

	'events' => [

		'Brickrouge\Document::render_title:before' => $hooks . 'before_document_render_title',
		'ICanBoogie\HTTP\Dispatcher::alter' => $hooks . 'on_http_dispatcher_alter',
		'ICanBoogie\SaveOperation::process' => $hooks . 'invalidate_cache',
		'ICanBoogie\DeleteOperation::process' => $hooks . 'invalidate_cache',
		'Icybee\Modules\Files\File::move' => $hooks . 'on_file_move',
		'Icybee\Modules\Pages\Page::move' => $hooks . 'on_page_move',
		'Icybee\Modules\Nodes\OnlineOperation::process' => $hooks . 'invalidate_cache',
		'Icybee\Modules\Nodes\OfflineOperation::process' => $hooks . 'invalidate_cache'

	],

	'prototypes' => [

		'Icybee\Modules\Sites\Site::lazy_get_home' => $hooks . 'get_home',
		'ICanBoogie\Core::get_page' => $hooks . 'get_page',
		__NAMESPACE__ . '\Page::render' => $hooks . 'render_page'

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

			__NAMESPACE__ . '\LanguagesElement::markup', [


			]

		],

		'navigation' => [

			$hooks . 'markup_navigation', [

				'parent' => 0,
				'depth' => [ 'default' => 2 ],
				'min-child' => false,
				'from-level' => null,
				'mode' => null

			]

		],

		'navigation:leaf' => [

			__NAMESPACE__ . '\NavigationBranchElement::markup_navigation_leaf', [

				/* FIXME-20120715: not implemented
				'level' => 1,
				'depth' => null,
				'title-link' => null
				*/

			]

		],

		'breadcrumb' => [

			__NAMESPACE__ . '\BreadcrumbElement::markup', [

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