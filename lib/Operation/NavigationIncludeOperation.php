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

use ICanBoogie\Errors;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;
use Icybee\Binding\Core\PrototypedBindings;

/**
 * @property Page $record
 *
 * @inheritdoc
 */
class NavigationIncludeOperation extends Operation
{
	use PrototypedBindings;

	protected function get_controls()
	{
		return [

			self::CONTROL_PERMISSION => Module::PERMISSION_MAINTAIN,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true

		] + parent::get_controls();
	}

	public function action(Request $request)
	{
		$this->module = $this->app->modules['pages'];

		return parent::action($request);
	}

	protected function validate(Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		$record = $this->record;
		$record->is_navigation_excluded = false;
		$record->save();

		return true;
	}
}
