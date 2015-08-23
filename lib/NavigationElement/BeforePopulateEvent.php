<?php

namespace Icybee\Modules\Pages\NavigationElement;

use ICanBoogie\Event;

use Icybee\Modules\Pages\Blueprint;
use Icybee\Modules\Pages\NavigationElement;

/**
 * Event class for the `Icybee\Modules\Pages\NavigationElement::populate:before` event.
 *
 * Third parties may use this event to create a subset of the blueprint.
 */
class BeforePopulateEvent extends Event
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
