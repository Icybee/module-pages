<?php

namespace Icybee\Modules\Pages\Page;

use ICanBoogie\Event;

use Icybee\Modules\Pages\Page;

class RenderRegionEvent extends Event
{
	/**
	 * Identifier of the region.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Page where the region is rendered.
	 *
	 * @var Page
	 */
	public $page;

	/**
	 * The region element.
	 *
	 * @var \Brickrouge\Element
	 */
	public $element;

	/**
	 * Reference to the rendered HTML of the region.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The event is constructed with the type `render_region`.
	 *
	 * @param Page $target
	 * @param array $payload
	 */
	public function __construct(Page $target, array $payload)
	{
		parent::__construct($target, 'render_region', $payload);
	}
}
