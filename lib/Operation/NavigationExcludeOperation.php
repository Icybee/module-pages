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

/**
 * @property Page $record
 *
 * @inheritdoc
 */
class NavigationExcludeOperation extends NavigationIncludeOperation
{
	protected function process()
	{
		$record = $this->record;
		$record->is_navigation_excluded = true;
		$record->save();

		return true;
	}
}
