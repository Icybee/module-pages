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

/**
 * Render a navigation branch.
 *
 * @property-read Page $start The starting parent page.
 * @property-read string $rendered_header The rendered header.
 * @property-read string $rendered_content The rendered content.
 */
class NavigationBranchElement extends Element
{
	const CSS_CLASS_NAMES = '#nav-branc-css-class-names';
	const DEPTH = '#nav-branch-depth';

	protected $page;

	/**
	 * Render a navigation leaf.
	 *
	 * <pre>
	 * <p:navigation:leaf
	 *     css-class-name = string
	 *     depth = int>
	 *     <!-- Content: p:with-param*, template? -->
	 * </p:navigation:leaf>
	 * </pre>
	 *
	 * `css-class-name` specifies the modifiers to use to generate the CSS classes of the header
	 * and the content nodes. It defaults to "active trail id". `depth` is the maximum depth of the
	 * branch. It defaults to 2.
	 *
	 * @param array $args
	 * @param \Patorn\Engine $patron
	 * @param mixed $template
	 *
	 * @return NavigationBranchElement|string
	 */
	static public function markup_navigation_leaf(array $args, $patron, $template)
	{
		global $core;

		$element = new static($core->request->context->page, [

			self::CSS_CLASS_NAMES => $args['css-class-names'],
			self::DEPTH => $args['depth']

		]);

		return $template ? $patron($template, $element) : $element;
	}

	/**
	 * Initialize the {@link $page} property.
	 *
	 * @param Page $page
	 * @param array $attributes with the following defaults:
	 * - {@link CSS_CLASS_NAMES}: "active trail id",
	 * - {@link DEPTH}: 2
	 */
	public function __construct(Page $page, array $attributes=[])
	{
		$this->page = $page;

		parent::__construct('div', $attributes + [

			'class' => 'nav-branch',

			self::CSS_CLASS_NAMES => 'active trail id',
			self::DEPTH => 2

		]);
	}

	/**
	 * Render the header and content elements.
	 *
	 * @throws ElementIsEmpty if the content is empty.
	 *
	 * @return string
	 */
	protected function render_inner_html()
	{
		$content = $this->rendered_content;

		if (!$content)
		{
			throw new ElementIsEmpty;
		}

		return $this->rendered_header . $content;
	}

	/**
	 * Bluid the {@link Blueprint} instance for the branch.
	 *
	 * @param Page $page
	 * @param int $start_id
	 *
	 * @return Blueprint
	 */
	protected function build_blueprint(Page $page, Page $start)
	{
		global $core;

		$trail = [];
		$p = $page;

		while ($p)
		{
			$trail[$p->nid] = $p->nid;

			$p = $p->parent;
		}

		/* @var $blueprint Blueprint */

		$blueprint = $core
		->models['pages']
		->blueprint($this->page->siteid)
		->subset($start->nid, $this[self::DEPTH], function(BlueprintNode $node) use($trail) {

			if (!$node->is_online || $node->is_navigation_excluded)
			{
				return true;
			}

			if (empty($trail[$node->nid]) && empty($trail[$node->parentid]))
			{
				return true;
			}

			return false;

		});

		new NavigationBranchElement\AlterBlueprintEvent($this, $blueprint, $page, $start);

		return $blueprint;
	}

	/**
	 * Return the starting page.
	 *
	 * @return Page
	 */
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

	/**
	 * Render the starting page as the navigation header.
	 *
	 * @return string
	 */
	protected function get_rendered_header()
	{
		$start = $this->start;
		$url = \Brickrouge\escape($start->url);
		$label = \Brickrouge\escape($start->label);
		$class = trim("nav-branch-header " . $start->css_class($this[self::CSS_CLASS_NAMES]));

		return <<<EOT
<div class="$class"><h5><a href="$url">$label</a></h5></div>
EOT;
	}

	/**
	 * Render the {@link Blueprint} as the navigation content.
	 *
	 * @return string|null
	 */
	protected function get_rendered_content()
	{
		$page = $this->page;
		$start = $this->start;
		$blueprint = $this->build_blueprint($page, $start);

		if (!$blueprint->tree)
		{
			return;
		}

		$pages = $blueprint->populate();
		$content = $this->render_page_recursive($blueprint->tree, $pages, $start->depth + 1, 0);

		return <<<EOT
<div class="nav-branch-content">$content</div>
EOT;
	}

	protected function render_page_recursive(array $children, $pages, $depth, $relative_depth)
	{
		$css_class_names = $this[self::CSS_CLASS_NAMES];

		/* @var $node BlueprintNode */

		$html = '';

		foreach ($children as $node)
		{
			$child = $pages[$node->nid];
			$css_class = $child->css_class($css_class_names);
			$url = \Brickrouge\escape($child->url);
			$label = \Brickrouge\escape($child->label);
			$rendered_children = null;

			if ($node->children)
			{
				$rendered_children = $this->render_page_recursive($node->children, $pages, $depth + 1, $relative_depth + 1);
			}

			$html .= <<<EOT
<li class="$css_class"><a href="$url">$label</a>$rendered_children</li>
EOT;
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

use Icybee\Modules\Pages\Blueprint;
use Icybee\Modules\Pages\NavigationBranchElement;
use Icybee\Modules\Pages\Page;

/**
 * Event class for the event `Icybee\Modules\Pages\NavigationBranchElement::alter_blueprint`.
 */
class AlterBlueprintEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the blueprint of the navigation branch.
	 *
	 * @var Blueprint
	 */
	public $blueprint;

	/**
	 * Page where the navigation branch is displayed.
	 *
	 * @var Page
	 */
	public $page;

	/**
	 * Identifier of the parent of the branch.
	 *
	 * @var Page
	 */
	public $start;

	/**
	 * The event is constructed with the type `alter_blueprint`.
	 *
	 * @param NavigationBranchElement $target
	 * @param Blueprint $blueprint
	 * @param Page $page
	 * @param Page $start
	 */
	public function __construct(NavigationBranchElement $target, Blueprint &$blueprint, Page $page, Page $start)
	{
		$this->blueprint = &$blueprint;
		$this->page = $page;
		$this->start = $start;

		parent::__construct($target, 'alter_blueprint');
	}
}
