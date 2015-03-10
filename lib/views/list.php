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

use Icybee\Modules\Views\View;

class ListView extends View
{
	protected function resolve_bind()
	{
		return $this->module->model->blueprint($this->app->site_id);
	}
}
