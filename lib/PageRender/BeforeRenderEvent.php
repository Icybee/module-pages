<?php

namespace Icybee\Modules\Pages\PageRenderer;

use Brickrouge\Document;
use ICanBoogie\Event;
use Icybee\Modules\Pages\Page;
use Icybee\Modules\Pages\PageRenderer;

/**
 * Event class for the 'Icybee\Modules\Pages\PageRenderer::render:before'.
 *
 * Third parties may use this event to alter the context of the rendering.
 */
class BeforeRenderEvent extends Event
{
	/**
	 * The {@link Page} being rendered.
	 *
	 * @var Page
	 */
	public $page;

	/**
	 * Document.
	 *
	 * @var Document
	 */
	public $document;

	/**
	 * Reference to the rendering context.
	 *
	 * @var array
	 */
	public $context;

	/**
	 * The event is constructed with the type `render:before`.
	 *
	 * @param PageRenderer $target
	 * @param Page $page
	 * @param Document $document
	 * @param array $context
	 */
	public function __construct(PageRenderer $target, Page $page, Document $document, &$context)
	{
		$this->page = $page;
		$this->document = $document;
		$this->context = &$context;

		parent::__construct($target, 'render:before');
	}
}
