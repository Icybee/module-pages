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

class ExportOperation extends \Icybee\Modules\Nodes\Operation\ExportOperation
{
	protected function process()
	{
		$records = parent::process();

		$keys = array_keys($records);

		$contents = $this->module
			->model('contents')
			->filter_by_page_id($keys)
			->all(\PDO::FETCH_OBJ);

		foreach ($contents as $content)
		{
			$records[$content->page_id]->contents[$content->content_id] = $content->content;
			$records[$content->page_id]->editors[$content->content_id] = $content->editor;
		}

		return $records;
	}
}
