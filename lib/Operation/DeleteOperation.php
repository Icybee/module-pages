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

use ICanBoogie\Errors;

class DeleteOperation extends \Icybee\Modules\Nodes\Operation\DeleteOperation
{
	protected function validate(Errors $errors)
	{
		$nid = $this->key;

		$count = $this->module->model->filter_by_parentid($nid)->count;

		if ($count)
		{
			$errors[] = $errors->format('This page has :count direct children.', [ ':count' => $count ]);
		}

		$count = $this->module->model->filter_by_locationid($nid)->count;

		if ($count)
		{
			$errors[] = $errors->format('This page is used in :count redirections.', [ ':count' => $count ]);
		}

		return parent::validate($errors);
	}
}
