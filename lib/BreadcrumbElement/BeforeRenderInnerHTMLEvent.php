<?php

namespace Icybee\Modules\Pages\BreadcrumbElement;

use ICanBoogie\Event;
use Icybee\Modules\Pages\BreadcrumbElement;
use Icybee\Modules\Pages\Page;

/**
 * Event class for the `Icybee\Modules\Pages\BreadcrumbElement::render_inner_html:before`
 * event.
 */
class BeforeRenderInnerHTMLEvent extends Event
{
	const TYPE = 'render_inner_html:before';

	/**
	 * Reference to the slices array.
	 *
	 * @var array
	 */
	public $slices;

	/**
	 * Reference to the divider.
	 *
	 * @var string
	 */
	public $divider;

	/**
	 * The page for which the breadcrumb is computed.
	 *
	 * @var Page.
	 */
	public $page;

	/**
	 * The event is constructed with the type `render_inner_html:before`.
	 *
	 * @param BreadcrumbElement $target
	 * @param array $payload
	 */
	public function __construct(BreadcrumbElement $target, array $payload)
	{
		parent::__construct($target, self::TYPE, $payload);
	}
}
