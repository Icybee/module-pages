<?php

namespace Icybee\Modules\Pages\BreadcrumbElement;

use ICanBoogie\Event;

use Icybee\Modules\Pages\BreadcrumbElement;
use Icybee\Modules\Pages\Page;

/**
 * Event class for the `Icybee\Modules\Pages\BreadcrumbElement::render_inner_html`
 * event.
 */
class RenderInnerHTMLEvent extends Event
{
	/**
	 * Reference to the inner HTML.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The page for which the breadcrumb is computed.
	 *
	 * @var Page
	 */
	public $page;

	/**
	 * The event is constructed with the type `render_inner_html`.
	 *
	 * @param BreadcrumbElement $target
	 * @param array $payload
	 */
	public function __construct(BreadcrumbElement $target, array $payload)
	{
		parent::__construct($target, 'render_inner_html', $payload);
	}
}
