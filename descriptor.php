<?php

namespace Icybee\Modules\Pages;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module;

return [

	Module::T_TITLE => 'Pages',
	Module::T_CATEGORY => 'site',
	Module::T_EXTENDS => 'nodes',
	Module::T_MODELS => [

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

	Module::T_NAMESPACE => __NAMESPACE__,
	Module::T_REQUIRED => true,
	Module::T_REQUIRES => [

		'editor' => '1.0'

	]

];