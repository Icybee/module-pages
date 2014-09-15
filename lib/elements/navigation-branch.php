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
use Brickrouge\ElementIsEmpty;

class NavigationBranchElement extends Element
{
	const CSS_CLASS_NAMES = '#nav-branc-css-class-names';

	protected $page;

	static public function markup_navigation_leaf(array $args, $patron, $template)
	{
		global $core;

		$element = new static($core->request->context->page, [

			self::CSS_CLASS_NAMES => $args['css-class-names']

		]);

		return $template ? $patron($template, $element) : $element;
	}

	public function __construct(Page $page, array $attributes=[])
	{
		$this->page = $page;

		parent::__construct('div', $attributes + [

			'class' => 'nav-branch',

			self::CSS_CLASS_NAMES => 'active trail'

		]);
	}

	protected function build_blueprint($page, $parent_id)
	{
		global $core;

		$trail = [];
		$p = $page;

		while ($p)
		{
			$trail[$p->nid] = $p->nid;

			$p = $p->parent;
		}

		$blueprint = $core->models['pages']->blueprint($this->page->siteid);

		$build_blueprint = function($parent_id, $max_depth) use (&$build_blueprint, $blueprint, $trail)
		{
			if (empty($blueprint->children[$parent_id]))
			{
				return;
			}

			$children = [];

			foreach ($blueprint->children[$parent_id] as $nid)
			{
				$page_blueprint = $blueprint->index[$nid];

				if (!$page_blueprint->is_online || $page_blueprint->is_navigation_excluded || $page_blueprint->pattern)
				{
					continue;
				}

				$page_blueprint = clone $page_blueprint;

				unset($page_blueprint->parent);
				$page_blueprint->children = [];

				if (isset($trail[$nid]) && $page_blueprint->depth < $max_depth)
				{
					$page_blueprint->children = $build_blueprint($nid, $max_depth);
				}

				$children[] = $page_blueprint;
			}

			return $children;
		};

		$blueprint =  $build_blueprint($parent_id, 2);

		new NavigationBranchElement\AlterBlueprintEvent($this, $blueprint, $page, $this->start->nid);

		return $blueprint;
	}

	protected function get_start()
	{
		$page = $this->page;
		$parent = $this->page;

		while ($parent->parent)
		{
			$parent = $parent->parent;
		}

		return $parent;
	}

	protected function get_rendered_header()
	{
		$start = $this->start;
		$url = \Brickrouge\escape($start->url);
		$label = \Brickrouge\escape($start->label);

		return <<<EOT
<div class="nav-branch-header"><h5><a href="$url">$label</a></h5></div>
EOT;
	}

	protected function render_inner_html()
	{
		global $core;

		$page = $this->page;
		$start = $this->start;
		$start_id = $start->nid;

		#

		$tree_blueprint = $this->build_blueprint($page, $start_id);

		if (!$tree_blueprint)
		{
			throw new ElementIsEmpty;
		}

		$ids = [];

		$collect_ids = function(array $blueprint) use(&$collect_ids, &$ids)
		{
			foreach ($blueprint as $page_blueprint)
			{
				$ids[] = $page_blueprint->nid;

				if ($page_blueprint->children)
				{
					$collect_ids($page_blueprint->children);
				}
			}
		};

		$html = $this->rendered_header;

		if ($tree_blueprint)
		{
			$collect_ids($tree_blueprint);

			$pages = $core->models['pages']->find($ids);
			$html .= '<div class="nav-branch-content">' . $this->render_page_recursive($tree_blueprint, $pages, $start->depth + 1, 0) . '</div>';
		}

		return $html;
	}

	protected function render_page_recursive(array $children, $pages, $depth, $relative_depth)
	{
		$class_names = $this[self::CSS_CLASS_NAMES];

		$html = '';

		foreach ($children as $blueprint_child)
		{
			$child = $pages[$blueprint_child->nid];

			$html .= '<li class="' . $child->css_class($class_names) . '"><a href="' . \Brickrouge\escape($child->url) . '">' . \Brickrouge\escape($child->label) . '</a>';

			if ($blueprint_child->children)
			{
				$html .= $this->render_page_recursive($blueprint_child->children, $pages, $depth + 1, $relative_depth + 1);
			}

			$html .= '</li>';
		}

		if ($html)
		{
			return <<<EOT
<ul class="nav nav-depth-$depth nav-relative-depth-$relative_depth">$html</ul>
EOT;
		}
	}
}

namespace Icybee\Modules\Pages\NavigationBranchElement;

/**
 * Event class for the event `Icybee\Modules\Pages\NavigationBranchElement::alter_blueprint`.
 */
class AlterBlueprintEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the blueprint of the navigation branch.
	 *
	 * @var \Icybee\Modules\Pages\Blueprint
	 */
	public $blueprint;

	/**
	 * Page where the navigation branch is displayed.
	 *
	 * @var \Icybee\Modules\Pages\Page
	 */
	public $page;

	/**
	 * Identifier of the parent of the branch.
	 *
	 * @var int
	 */
	public $parent_id;

	/**
	 * The event is constructed with the type `alter_blueprint`.
	 *
	 * @param \Icybee\Modules\Pages\NavigationBranchElement $target
	 * @param \Icybee\Modules\Pages\Blueprint $blueprint
	 * @param \Icybee\Modules\Pages\Page $page
	 * @param int $parent_id
	 */
	public function __construct(\Icybee\Modules\Pages\NavigationBranchElement $target, array &$blueprint, \Icybee\Modules\Pages\Page $page, $parent_id)
	{
		$this->blueprint = &$blueprint;
		$this->page = $page;
		$this->parent_id = $parent_id;

		parent::__construct($target, 'alter_blueprint');
	}
}