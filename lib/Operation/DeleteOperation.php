<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\Operation;

use ICanBoogie\ErrorCollection;

class DeleteOperation extends \Icybee\Modules\Nodes\Operation\DeleteOperation
{
	/**
	 * @inheritdoc
	 */
	protected function validate(ErrorCollection $errors)
	{
		$nid = $this->key;

		$count = $this->module->model->filter_by_parent_id($nid)->count;

		if ($count)
		{
			$errors->add_generic('This page has :count direct children.', [ ':count' => $count ]);
		}

		$count = $this->module->model->filter_by_location_id($nid)->count;

		if ($count)
		{
			$errors->add_generic('This page is used in :count redirections.', [ ':count' => $count ]);
		}

		return parent::validate($errors);
	}
}
