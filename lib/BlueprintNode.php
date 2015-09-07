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

use ICanBoogie\ActiveRecord;

/**
 * A node of the blueprint.
 *
 * @property-read int $children_count The number of children.
 * @property-read ActiveRecord[] $descendants The descendants ordered according to their
 * position and relation.
 * @property-read int $descendants_count The number of descendants.
 * @property Page $record.
 *
 * @see Blueprint
 */
class BlueprintNode
{
	/**
	 * The identifier of the page.
	 *
	 * @var int
	 */
	public $nid;

	/**
	 * Depth of the node is the tree.
	 *
	 * @var int
	 */
	public $depth;

	/**
	 * The identifier of the parent of the page.
	 *
	 * @var int
	 */
	public $parent_id;

	/**
	 * Blueprint node of the parent of the page.
	 *
	 * @var BlueprintNode
	 */
	public $parent;

	/**
	 * The children of the node.
	 *
	 * @var array[int]BlueprintNode
	 */
	public $children;

	/**
	 * Inaccessible properties are obtained from the record.
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property)
		{
			case 'children_count': return count($this->children);
			case 'descendants': return $this->get_descendants();
			case 'descendants_count': return $this->get_descendants_count();
		}

		return $this->record->$property;
	}

	/**
	 * Return the descendant nodes of the node.
	 *
	 * @return int
	 */
	protected function get_descendants()
	{
		$descendants = [];

		foreach ($this->children as $nid => $child)
		{
			$descendants[$nid] = $child;
			$descendants += $child->descendants;
		}

		return $descendants;
	}

	/**
	 * Return the number of descendants.
	 *
	 * @return int
	 */
	protected function get_descendants_count()
	{
		$n = 0;

		foreach ($this->children as $child)
		{
			$n += 1 + $child->descendants_count;
		}

		return $n;
	}

	/**
	 * Forwards calls to the record.
	 *
	 * @param string $method
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		return call_user_func_array([ $this->record, $method ], $arguments);
	}
}
