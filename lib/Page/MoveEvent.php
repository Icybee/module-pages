<?php

namespace Icybee\Modules\Pages\Page;

use ICanBoogie\Event;

use Icybee\Modules\Pages\Page;

/**
 * Event class for the `Icybee\Modules\Pages\Page::move` event.
 */
class MoveEvent extends Event
{
	/**
	 * Previous path.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * New path.
	 *
	 * @var string
	 */
	public $to;

	/**
	 * The event is constructed with the type `move`.
	 *
	 * @param Page $target
	 * @param string $from Previous path.
	 * @param string $to New path.
	 */
	public function __construct(Page $target, $from, $to)
	{
		$this->from = $from;
		$this->to = $to;

		parent::__construct($target, 'move');
	}
}
