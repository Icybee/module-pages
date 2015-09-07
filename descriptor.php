<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return [

	Descriptor::TITLE => 'Pages',
	Descriptor::CATEGORY => 'site',
	Descriptor::ID => 'pages',
	Descriptor::INHERITS => 'nodes',
	Descriptor::MODELS => [

		'primary' => [

			Model::EXTENDING => 'nodes',
			Model::SCHEMA => [

				'parent_id' => 'foreign',
				'location_id' => 'foreign',
				'label' => [ 'varchar', 80 ],
				'pattern' => 'varchar',
				'weight' => [ 'integer', 'unsigned' => true ],
				'template' => [ 'varchar', 32 ],
				'is_navigation_excluded' => [ 'boolean', 'indexed' => true ]

			]
		],

		'contents' => [

			Model::SCHEMA => [

				'page_id' => [ 'foreign', 'primary' => true ],
				'content_id' => [ 'varchar', 64, 'primary' => true ],
				'content' => [ 'text', 'long' ],
				'editor' => [ 'varchar', 32 ]

			]
		]
	],

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRED => true,
	Descriptor::REQUIRES => [ 'editor' ]
];
