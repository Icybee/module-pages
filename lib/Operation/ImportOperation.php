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

use ICanBoogie\HTTP\Request;

class ImportOperation extends \Icybee\Modules\Nodes\Operation\ImportOperation
{
	private $parent_id = [];
	private $location_id = [];

	protected function parse_data(array $data)
	{
		foreach ($data as $nid => $obj)
		{
			if ($obj->parent_id)
			{
				$this->parent_id[$nid] = $obj->parent_id;
			}

			unset($obj->parent_id);

			if ($obj->location_id)
			{
				$this->location_id[$nid] = $obj->location_id;
			}

			unset($obj->location_id);

			if (empty($obj->contents))
			{
				\ICanBoogie\log("page $nid has no content");
			}
			else
			{
				$contents = (array) $obj->contents;
				$editors = (array) $obj->editors;

				foreach ($contents as $content_id => &$content)
				{
					if (($content{0} == '{' || $content{0} == '[') && $content{1} == '"')
					{
						$content = json_decode($content, true);
					}
				}

				foreach ($editors as $content_id => $editor_name)
				{
					if ($editor_name != 'widgets' || empty($contents[$content_id]))
					{
						continue;
					}

					$content = &$contents[$content_id];
					$content = array_combine($content, array_fill(0, count($content), 'on'));
				}

				$obj->contents = $contents;
				$obj->editors = $editors;
			}
		}

		return parent::parse_data($data);
	}

	protected function import(array $data, Request $save)
	{
		parent::import($data, $save);

		//var_dump($this->keys_translations, $this->location_id, $data);

		$update = $this->app->db->prepare('UPDATE {prefix}pages SET parent_id = ?, location_id = ? WHERE nid = ?');

		$original_nodes_with_parent_id = $this->parent_id;
		$original_nodes_with_location_id = $this->location_id;

		foreach (array_keys($data) as $nid)
		{
			$parent_id = 0;

			if (isset($original_nodes_with_parent_id[$nid]))
			{
				$parent_id = $this->keys_translations[$original_nodes_with_parent_id[$nid]];
			}

			$location_id = 0;

			if (isset($original_nodes_with_location_id[$nid]))
			{
				$location_id = $this->keys_translations[$original_nodes_with_location_id[$nid]];
			}

			if ($parent_id || $location_id)
			{
				$update->execute([ $parent_id, $location_id, $this->keys_translations[$nid] ]);
			}
		}
	}
}
