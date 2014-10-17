<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return [

	Descriptor::TITLE => 'Pages',
	Descriptor::CATEGORY => 'site',
	Descriptor::INHERITS => 'nodes',
	Descriptor::MODELS => [

		'primary' => [

			Model::EXTENDING => 'nodes',
			Model::SCHEMA => [

				'fields' => [

					'parentid' => 'foreign',
					'locationid' => 'foreign',
					'label' => [ 'varchar', 80 ],
					'pattern' => 'varchar',
					'weight' => [ 'integer', 'unsigned' => true ],
					'template' => [ 'varchar', 32 ],
					'is_navigation_excluded' => [ 'boolean', 'indexed' => true ]

				]

			]

		],

		'contents' => [

			Model::SCHEMA => [

				'fields' => [

					'pageid' => [ 'foreign', 'primary' => true ],
					'contentid' => [ 'varchar', 64, 'primary' => true ],
					'content' => [ 'text', 'long' ],
					'editor' => [ 'varchar', 32 ]

				]

			]

		]

	],

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRED => true,
	Descriptor::REQUIRES => [

		'editor' => '1.0'

	]

];