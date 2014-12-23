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

use Brickrouge\A;
use Brickrouge\Element;

use Icybee\Modules\Pages\NavigationElement\BeforePopulateEvent;
use Icybee\Modules\Pages\NavigationElement\PopulateEvent;

/**
 * A navigation element.
 *
 * @property-read Blueprint $blueprint The blueprint of the navigation.
 */
class NavigationElement extends Element
{
	const CSS_CLASS_NAMES = '#navigation-css-class-names';
	const CSS_CLASS_NAMES_DEFAULT = '-constructor -slug -template';

	protected $blueprint;

	/**
	 * Return the blueprint of the navigation.
	 *
	 * @return Blueprint
	 */
	protected function get_blueprint()
	{
		return $this->blueprint;
	}

	/**
	 * Initialize the {@link $blueprint} property.
	 *
	 * @param Blueprint $blueprint
	 * @param string $type
	 * @param array $attributes
	 */
	public function __construct(Blueprint $blueprint, $type='ol', array $attributes=[])
	{
		$this->blueprint = $blueprint;

		parent::__construct($type, $attributes + [

			self::CSS_CLASS_NAMES => self::CSS_CLASS_NAMES_DEFAULT,

			'class' => 'nav lv1'

		]);
	}

	/**
	 * Render the element.
	 *
	 * The method emits the {@link BeforePopulateEvent} and {@link PopulateEvent} events.
	 */
	public function render()
	{
		$blueprint = $this->blueprint;

		new BeforePopulateEvent($this, $blueprint);

		$blueprint->populate();

		$this->create_renderables($blueprint);
		$children = $this->create_children($blueprint);

		new PopulateEvent($this, $children, $blueprint);

		$this[self::CHILDREN] = $children;

		return parent::render();
	}

	/**
	 * Creates the renderable elements for each node of the blueprint.
	 *
	 * The renderable elements of a node are stored in the `renderables` property. The following
	 * elements are created:
	 *
	 * - `link`: A `A` element with the record's URL as `href` and the record's label as
	 * inner HTML.
	 * - `item_decorator`: A `LI` element used to contain the rendered content of the node. The
	 * class of the element is created with the `css_class()` method of the record and the
	 * following modifier "-constructor -slug -template".
	 * - `menu`: A `OL` element with the class "dropdown-menu". The element is only created if the
	 * node has children, otherwise `menu` is `null`.
	 *
	 * @param Blueprint $blueprint
	 */
	protected function create_renderables(Blueprint $blueprint)
	{
		$css_class_names = $this[self::CSS_CLASS_NAMES];

		foreach ($blueprint as $node)
		{
			$record = $node->record;

			$node->renderables = [

				'link' => new Element('a', [

					Element::INNER_HTML => \Brickrouge\escape($record->label),

					'href' => $record->url

				]),

				'item_decorator' => new Element('li', [

					'class' => $record->css_class($css_class_names)

				]),

				'menu' => null

			];

			if ($node->children)
			{
				$node->renderables['menu'] = new Element('ol', [

					'class' => "dropdown-menu"

				]);
			}
		}
	}

	/**
	 * Create the children of the element.
	 *
	 * The renderables associated with the nodes are used to build the children array.
	 *
	 * @param Blueprint $blueprint
	 *
	 * @return Element[]
	 */
	protected function create_children(Blueprint $blueprint)
	{
		$render_node = function(BlueprintNode $node, $depth=1) use(&$render_node) {

			$renderables = $node->renderables;
			$item_decorator = $renderables['item_decorator'];
			$item_decorator->adopt($renderables['link']);

			if (empty($renderables['menu']))
			{
				return $item_decorator;
			}

			$children = [];

			foreach ($node->children as $child)
			{
				$children[] = $render_node($child, $depth + 1);
			}

			$menu = $renderables['menu'];
			$menu->adopt($children);
			$menu->add_class("lv{$depth}");

			$item_decorator->adopt($menu);

			return $item_decorator;
		};

		$children = [];

		foreach ($blueprint->tree as $node)
		{
			$children[] = $render_node($node);
		}

		return $children;
	}
}

/*
 * Events
 */

namespace Icybee\Modules\Pages\NavigationElement;

use Icybee\Modules\Pages\Blueprint;
use Icybee\Modules\Pages\NavigationElement;

/**
 * Event class for the `Icybee\Modules\Pages\NavigationElement::populate:before` event.
 *
 * Third parties may use this event to create a subset of the blueprint.
 */
class BeforePopulateEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the blueprint.
	 *
	 * @var Blueprint
	 */
	public $blueprint;

	public function __construct(NavigationElement $target, Blueprint &$blueprint)
	{
		$this->blueprint = &$blueprint;

		parent::__construct($target, 'populate:before');
	}
}

/**
 * Event class for the `Icybee\Modules\Pages\NavigationElement::populate` event.
 *
 * Third parties may use this event to alter the renderable elements of the navigation. For
 * instance, one can replace links, classes or titles.
 *
 * The following example demonstrates how to alter the `href` and `target` attributes of
 * navigation links:
 *
 * <pre>
 * <?php
 *
 * use Icybee\Modules\Pages\NavigationElement;
 *
 * $app->events->attach(function(NavigationElement\PopulateEvent $event, NavigationElement $target) {
 *
 *     foreach ($event->blueprint as $node)
 *     {
 *         $link = $node->renderables['link'];
 *
 *         $link['href'] = '#';
 *         $link['target'] = '_blank';
 *     }
 *
 * });
 * </pre>
 */
class PopulateEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the children array.
	 *
	 * @var array
	 */
	public $children;

	/**
	 * Reference to the blueprint.
	 *
	 * @var Blueprint
	 */
	public $blueprint;

	public function __construct(NavigationElement $target, array &$children, Blueprint &$blueprint)
	{
		$this->children = &$children;
		$this->blueprint = &$blueprint;

		parent::__construct($target, 'populate');
	}
}
