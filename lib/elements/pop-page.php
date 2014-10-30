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

class PopPage extends Element
{
	public function render()
	{
		global $core;

		/* @var $blueprint Blueprint */

		$blueprint = $core->models['pages']->blueprint($core->site_id);
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
