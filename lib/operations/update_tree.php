<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages;

use ICanBoogie\I18n\FormattedString;

/**
 * Updates the order and relation of the specified records.
 */
class UpdateTreeOperation extends \ICanBoogie\Operation
{
	protected function get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => Module::PERMISSION_ADMINISTER
		)

		+ parent::get_controls();
	}

	protected function validate(\ICanboogie\Errors $errors)
	{
		$order = $this->request['order'];
		$relation = $this->request['relation'];

		if ($order && $relation)
		{
			foreach ($order as $nid)
			{
				if (!isset($relation[$nid]))
				{
					$errors['relation'] = new FormattedString("Missing relation for nid %nid.", array('nid' => $nid));
				}
			}
		}
		else
		{
			if (!$order)
			{
				$errors['order'] = new FormattedString("The %param param is required", array('param' => 'order'));
			}

			if (!$relation)
			{
				$errors['relation'] = new FormattedString("The %param param is required", array('param' => 'relation'));
			}
		}

		return !$errors->count();
	}

	protected function process()
	{
		$w = 0;
		$update = $this->module->model->prepare('UPDATE {self} SET `parentid` = ?, `weight` = ? WHERE `{primary}` = ? LIMIT 1');

		$order = $this->request['order'];
		$relation = $this->request['relation'];

		foreach ($order as $nid)
		{
			$parent_id = $relation[$nid];

			// FIXME-20100429: cached entries are not updated here, we should flush the cache.

			$update($parent_id, $w++, $nid);
		}

		return true;
	}
}