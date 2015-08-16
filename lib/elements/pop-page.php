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

use Brickrouge\Element;
use Icybee\Binding\ObjectBindings;

class PopPage extends Element
{
	use ObjectBindings;

	public function render()
	{
		$app = $this->app;

		/* @var $blueprint Blueprint */

		$blueprint = $app->models['pages']->blueprint($app->site_id);
		$blueprint->populate();

		$options = [];

		foreach ($blueprint->ordered_records as $record)
		{
			$options[$record->nid] = str_repeat("\xC2\xA0", $record->depth * 4) . $record->label;
		}

		$this[self::OPTIONS] = [ null => '' ] + $options;

		return parent::render();
	}
}
