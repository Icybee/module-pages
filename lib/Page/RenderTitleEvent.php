<?php

namespace Icybee\Modules\Pages\Page;

use ICanBoogie\Event;

use Icybee\Modules\Pages\Page;

class RenderTitleEvent extends Event
{
	/**
	 * Title of the page.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Reference to the rendered title of the page.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The event is constructed with the type `render_title`.
	 *
	 * @param Page $target
	 * @param array $payload
	 */
	public function __construct(Page $target, array $payload)
	{
		parent::__construct($target, 'render_title', $payload);
	}
}
