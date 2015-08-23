<?php

namespace Icybee\Modules\Pages\NavigationElement;

use ICanBoogie\Event;

use Icybee\Modules\Pages\Blueprint;
use Icybee\Modules\Pages\NavigationElement;

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
class PopulateEvent extends Event
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
