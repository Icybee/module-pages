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

use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\PropertyNotDefined;

/**
 * A simplified data structure representing the relationship between pages.
 *
 * @property-read BlueprintNode[] $ordered_nodes Blueprint nodes ordered according to their
 * position and relation. See: {@link get_ordered_nodes()}.
 * @property-read Page[] $ordered_records Records ordered according to their position and
 * relation. See: {@link get_ordered_records()}.
 *
 * @see BlueprintNode
 */
class Blueprint implements \IteratorAggregate
{
	/**
	 * Creates a {@link Blueprint} instance from an {@link ActiveRecord\Query}.
	 *
	 * @param Query $query
	 *
	 * @return Blueprint
	 */
	static public function from(Query $query)
	{
		$query->mode(\PDO::FETCH_CLASS, __NAMESPACE__ . '\BluePrintNode');

		$relation = [];
		$children = [];
		$index = [];

		foreach ($query as $row)
		{
			$row->parent = null;
			$row->depth = null;
			$row->children = [];

			$nid = $row->nid;
			$parent_id = $row->parentid;
			$index[$nid] = $row;
			$relation[$nid] = $parent_id;
			$children[$parent_id][$nid] = $nid;
		}

		$tree = [];

		foreach ($index as $nid => $page)
		{
			if (!$page->parentid || empty($index[$page->parentid]))
			{
				$tree[$nid] = $page;

				continue;
			}

			$page->parent = $index[$page->parentid];
			$page->parent->children[$nid] = $page;
		}

		self::set_depth($tree);

		return new static($query->model, $relation, $children, $index, $tree);
	}

	/**
	 * Set the depth of the nodes of the specified branch.
	 *
	 * @param array $branch
	 * @param number $depth Starting depth.
	 */
	static private function set_depth(array $branch, $depth=0)
	{
		foreach ($branch as $node)
		{
			$node->depth = $depth;

			if (!$node->children)
			{
				continue;
			}

			self::set_depth($node->children, $depth + 1);
		}
	}

	/**
	 * The child/parent relation.
	 *
	 * An array where each key/value is the identifier of a node and the idenfier of its parent,
	 * or zero if the node has no parent.
	 *
	 * @var array[int]int
	 */
	public $relation;

	/**
	 * The parent/children relation.
	 *
	 * An array where each key/value is the identifier of a parent and an array made of the
	 * identifiers of its children. Each key/value pair of the children value is made of the
	 * child identifier.
	 *
	 * @var array[int]array
	 */
	public $children;

	/**
	 * Index of the blueprint nodes.
	 *
	 * Blueprint nodes are instances of the {@link BlueprintNode} class. The key of the index is
	 * the identifier of the node, while the value is the node instance.
	 *
	 * @var array[int]BlueprintNode
	 */
	public $index;

	/**
	 * Pages nested as a tree.
	 *
	 * @var array[int]BlueprintNode
	 */
	public $tree;

	/**
	 * Model associated with the blueprint.
	 *
	 * @var \ICanBoogie\ActiveRecord\Model
	 */
	public $model;

	/**
	 * The blueprint is usually constructed by the {@link Model::blueprint()} method or the
	 * {@link subset()} method.
	 *
	 * @param Model $model
	 * @param array $relation The child/parent relations.
	 * @param array $children The parent/children relations.
	 * @param array $index Pages index.
	 * @param array $tree Pages nested as a tree.
	 */
	protected function __construct(Model $model, array $relation, array $children, array $index, array $tree)
	{
		$this->relation = $relation;
		$this->children = $children;
		$this->index = $index;
		$this->tree = $tree;
		$this->model = $model;
	}

	/**
	 * Support for the {@link $ordered_records} property.
	 *
	 * @param string $property
	 *
	 * @throws PropertyNotDefined in attempt to get a property that is not defined or supported.
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property)
		{
			case 'ordered_nodes';

				return $this->get_ordered_nodes();

			case 'ordered_records':

				return $this->get_ordered_records();
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->index);
	}

	protected function get_ordered_nodes()
	{
		$nodes = [];

		$ordering = function(array $branch) use(&$ordering, &$nodes) {

			foreach ($branch as $node)
			{
				$nodes[$node->nid] = $node;

				if ($node->children)
				{
					$ordering($node->children);
				}
			}
		};

		$ordering($this->tree);

		return $nodes;
	}

	/**
	 * Returns the records of the blueprint ordered according to their position and relation.
	 *
	 * Note: The blueprint is populated with records if needed.
	 *
	 * @return array[int]ActiveRecord
	 */
	protected function get_ordered_records()
	{
		$records = [];

		$ordering = function(array $branch) use(&$ordering, &$records) {

			foreach ($branch as $node)
			{
				$records[$node->nid] = $node->record;

				if ($node->children)
				{
					$ordering($node->children);
				}
			}
		};

		$node = current($this->index);

		if (empty($node->record))
		{
			$this->populate();
		}

		$ordering($this->tree);

		return $records;
	}

	/**
	 * Checks if a branch has children.
	 *
	 * @param int $nid Identifier of the parent record.
	 *
	 * @return boolean
	 */
	public function has_children($nid)
	{
		return !empty($this->children[$nid]);
	}

	/**
	 * Returns the number of children of a branch.
	 *
	 * @param int $nid The identifier of the parent record.
	 *
	 * @return int
	 */
	public function children_count($nid)
	{
		return $this->has_children($nid) ? count($this->children[$nid]) : 0;
	}

	/**
	 * Create a subset of the blueprint.
	 *
	 * A filter can be specified to filter out the nodes of the subset. The function returns `true`
	 * to discard a node. The callback function have the following signature:
	 *
	 *     function(BlueprintNode $node)
	 *
	 * The following example demonstrate how offline nodes can be filtered out.
	 *
	 * <pre>
	 * <?php
	 *
	 * use Icybee\Modules\Pages\BlueprintNode;
	 *
	 * $subset = $app->models['pages']
	 * ->blueprint($site_id = 1)
	 * ->subset(null, null, function(BlueprintNode $node) {
	 *
	 *     return !$node->is_online;
	 *
	 * });
	 * </pre>
	 *
	 * @param int $nid_or_filter Identifier of the starting branch, or a closure
	 * to filter the nodes.
	 * @param int $depth Maximum depth of the subset.
	 * @param callable $filter A filter callback.
	 *
	 * @return Blueprint
	 */
	public function subset($nid_or_filter=null, $depth=null, $filter=null)
	{
		$relation = [];
		$children = [];
		$index = [];

		$nid = $nid_or_filter;

		if ($nid_or_filter instanceof \Closure)
		{
			$nid = null;
			$filter = $nid_or_filter;
		}

		$iterator = function(array $branch) use(&$iterator, &$filter, &$depth, &$relation, &$children, &$index)
		{
			$pages = [];

			foreach ($branch as $nid => $node)
			{
				$node_children = $node->children;
				$node = clone $node;
				$node->children = [];

				if ($node_children && ($depth === null || $node->depth < $depth))
				{
					$node->children = $iterator($node_children);
				}

				if ($filter && $filter($node))
				{
					continue;
				}

				$parentid = $node->parentid;

				$relation[$nid] = $parentid;
				$children[$parentid][] = $nid;
				$pages[$nid] = $node;
				$index[$nid] = $node;
			}

			return $pages;
		};

		$tree = $iterator($nid ? $this->index[$nid]->children : $this->tree);

		return new static($this->model, $relation, $children, $index, $tree);
	}

	/**
	 * Populates the blueprint by loading the associated records.
	 *
	 * The method adds the `record` property to the blueprint nodes.
	 *
	 * @return array[int]\Icybee\Modules\Pages\Page
	 */
	public function populate()
	{
		if (!$this->index)
		{
			return [];
		}

		$records = $this->model->find(array_keys($this->index));

		foreach ($records as $nid => $record)
		{
			$this->index[$nid]->record = $record;
		}

		return $records;
	}
}

/**
 * A node of the blueprint.
 *
 * @property-read int $children_count The number of children.
 * @property-read array[int]ActiveRecord $descendents The descendents ordered according to their
 * position and relation.
 * @property-read int $descendents_count The number of descendents.
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
	public $parentid;

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
	 */
	public function __get($property)
	{
		switch ($property)
		{
			case 'children_count': return count($this->children);
			case 'descendents': return $this->get_descendents();
			case 'descendents_count': return $this->get_descendents_count();
		}

		return $this->record->$property;
	}

	/**
	 * Return the descendent nodes of the node.
	 *
	 * @return int
	 */
	protected function get_descendents()
	{
		$descendents = [];

		foreach ($this->children as $nid => $child)
		{
			$descendents[$nid] = $child;
			$descendents += $child->descendents;
		}

		return $descendents;
	}

	/**
	 * Return the number of descendents.
	 *
	 * @return int
	 */
	protected function get_descendents_count()
	{
		$n = 0;

		foreach ($this->children as $child)
		{
			$n += 1 + $child->descendents_count;
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
		return call_user_func_array($this->record, $method, $arguments);
	}
}
