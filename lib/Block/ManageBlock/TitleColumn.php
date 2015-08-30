<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\Block\ManageBlock;

use Brickrouge\Element;
use Brickrouge\Text;

use Icybee\Modules\Nodes\Block\ManageBlock\EditDecorator;
use Icybee\Modules\Pages\Block\ManageBlock;
use Icybee\Modules\Pages\Page;

class TitleColumn extends \Icybee\Modules\Nodes\Block\ManageBlock\TitleColumn
{
	/**
	 * @var ManageBlock
	 */
	public $manager;

	/**
	 * @param Page $record
	 *
	 * @return string
	 */
	public function render_cell($record)
	{
		$rc = '';

		if ($this->manager->mode == 'tree')
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $record->depth);
			$rc .= '<div class="handle"><i class="icon-move"></i></div>';

			if (0)
			{
				$rc .= new Text([

					Element::LABEL => 'w',
					Element::LABEL_POSITION => 'before',
					'name' => 'weights[' . $record->nid . ']',
					'value' => $record->weight,
					'size' => 3,
					'style' => 'border: none; background: transparent; color: green'

				]);

				$rc .= '&nbsp;';

				$rc .= new Text([

					Element::LABEL => 'p',
					Element::LABEL_POSITION => 'before',
					'name' => 'parents[' . $record->nid . ']',
					'value' => $record->parentid,
					'size' => 3,
					'style' => 'border: none; background: transparent; color: green'

				]);
			}
			else
			{
				$rc .= new Element('input', [

					'name' => 'parents[' . $record->nid . ']',
					'type' => 'hidden',
					'value' => $record->parentid

				]);
			}
		}

		$rc .= new EditDecorator($record->label, $record);

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $record->nid . '</small>';
		}

		if ($this->manager->mode == 'tree' && $record->has_child)
		{
			$expanded = in_array($record->nid, $this->manager->options->expanded);

			$rc .= ' <a class="treetoggle" href="?' . ($expanded ? 'collapse' : 'expand') . '=' . $record->nid . '">' . ($expanded ? '-' : "+{$record->descendants_count}") . '</a>';
		}

		#
		# updated_at
		#

		$now = time();
		$updated_at = strtotime($record->updated_at);

		if ($now - $updated_at < 7200)
		{
			$rc .= ' <sup style="vertical-align: text-top; color: red;">Récemment modifié</sup>';
		}

		return $rc;
	}
}
