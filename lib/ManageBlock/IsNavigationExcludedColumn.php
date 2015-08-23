<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\ManageBlock;

use Brickrouge\Element;

use Icybee\ManageBlock\BooleanColumn;
use Icybee\Modules\Pages\ManageBlock;
use Icybee\Modules\Pages\Page;

/**
 * Representation of the `is_navigation_excluded` column.
 */
class IsNavigationExcludedColumn extends BooleanColumn
{
	public function __construct(ManageBlock $manager, $id, array $options = [])
	{
		parent::__construct($manager, $id, $options + [

			'title' => null,
			'filters' => [

				'options' => [

					'=1' => 'Excluded from navigation',
					'=0' => 'Included in navigation'

				]

			]

		]);
	}

	/**
	 * @param Page $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		return new Element('i', [

			'class' => 'icon-sitemap trigger ' . ($record->is_navigation_excluded ? 'on' : ''),
			'data-nid' => $record->nid,
			'title' => "Inclure ou exclure la page du menu de navigation principal"

		]);
	}
}
