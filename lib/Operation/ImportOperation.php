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
	private $locationid = [];

	protected function parse_data(array $data)
	{
		foreach ($data as $nid => $obj)
		{
			if ($obj->parent_id)
			{
				$this->parent_id[$nid] = $obj->parent_id;
			}

			unset($obj->parent_id);

			if ($obj->locationid)
			{
				$this->locationid[$nid] = $obj->locationid;
			}

			unset($obj->locationid);

			if (empty($obj->contents))
			{
				\ICanBoogie\log("page $nid has no content");
			}
			else
			{
				$contents = (array) $obj->contents;
				$editors = (array) $obj->editors;

				foreach ($contents as $contentid => &$content)
				{
					if (($content{0} == '{' || $content{0} == '[') && $content{1} == '"')
					{
						$content = json_decode($content, true);
					}
				}

				foreach ($editors as $contentid => $editor_name)
				{
					if ($editor_name != 'widgets' || empty($contents[$contentid]))
					{
						continue;
					}

					$content = &$contents[$contentid];
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

		//var_dump($this->keys_translations, $this->locationid, $data);

		$update = $this->app->db->prepare('UPDATE {prefix}pages SET parent_id = ?, locationid = ? WHERE nid = ?');

		$original_nodes_with_parent_id = $this->parent_id;
		$original_nodes_with_locationid = $this->locationid;

		foreach (array_keys($data) as $nid)
		{
			$parent_id = 0;

			if (isset($original_nodes_with_parent_id[$nid]))
			{
				$parent_id = $this->keys_translations[$original_nodes_with_parent_id[$nid]];
			}

			$locationid = 0;

			if (isset($original_nodes_with_locationid[$nid]))
			{
				$locationid = $this->keys_translations[$original_nodes_with_locationid[$nid]];
			}

			if ($parent_id || $locationid)
			{
				$update->execute([ $parent_id, $locationid, $this->keys_translations[$nid] ]);
			}
		}
	}
}
