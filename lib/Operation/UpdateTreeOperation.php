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
use ICanBoogie\Operation;
use Icybee\Modules\Files\Module;

/**
 * Updates the order and relation of the specified records.
 */
class UpdateTreeOperation extends Operation
{
	protected function get_controls()
	{
		return [

			self::CONTROL_PERMISSION => Module::PERMISSION_ADMINISTER

		] + parent::get_controls();
	}

	protected function validate(Errors $errors)
	{
		$order = $this->request['order'];
		$relation = $this->request['relation'];

		if ($order && $relation)
		{
			foreach ($order as $nid)
			{
				if (!isset($relation[$nid]))
				{
					$errors['relation'] = $errors->format("Missing relation for nid %nid.", [ 'nid' => $nid ]);
				}
			}
		}
		else
		{
			if (!$order)
			{
				$errors['order'] = $errors->format("The %param param is required", [ 'param' => 'order' ]);
			}

			if (!$relation)
			{
				$errors['relation'] = $errors->format("The %param param is required", [ 'param' => 'relation' ]);
			}
		}

		return !$errors->count();
	}

	protected function process()
	{
		$update = $this->module->model->prepare("UPDATE {self} SET `parentid` = ?, `weight` = ? WHERE `{primary}` = ? LIMIT 1");

		$order = $this->request['order'];
		$relation = $this->request['relation'];
		$w = 0;

		foreach ($order as $nid)
		{
			$parent_id = $relation[$nid];

			// FIXME-20100429: cached entries are not updated here, we should flush the cache.

			$update($parent_id, $w++, $nid);
		}

		return true;
	}
}
