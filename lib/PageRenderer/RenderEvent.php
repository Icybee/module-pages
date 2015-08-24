<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\PageRenderer;

use Brickrouge\Document;
use ICanBoogie\Event;
use Icybee\Modules\Pages\Page;
use Icybee\Modules\Pages\PageRenderer;

/**
 * Event class for the `Icybee\Modules\Pages\PageRenderer::render` event.
 *
 * Third parties may use this event to alter the renderer HTML.
 */
class RenderEvent extends Event
{
	/**
	 * Reference to the rendered HTML.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The page being rendered.
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
	 * The event is constructed with the type `render`.
	 *
	 * @param PageRenderer $target
	 * @param string $html Reference to the rendered HTML.
	 * @param Page $page The page being rendered.
	 * @param Document $document
	 */
	public function __construct(PageRenderer $target, &$html, Page $page, Document $document)
	{
		$this->html = &$html;
		$this->page = $page;
		$this->document = $document;

		parent::__construct($target, 'render');
	}

	/**
	 * Inject a HTML fragment.
	 *
	 * @param string $fragment
	 * @param string $selector
	 * @param string $where
	 *
	 * @throws \InvalidArgumentException If `$where` is not 'bottom', the only value currently
	 * supported.
	 * @throws \Exception If the position where to insert the fragment cannot be resolved.
	 */
	public function inject($fragment, $selector, $where = 'bottom')
	{
		if ($where != 'bottom')
		{
			throw new \InvalidArgumentException(\ICanBoogie\format("Only the 'bottom' position is currently supported. Given: %where", [

				'where' => $where

			]));
		}

		$markup = $selector{0} == '<' ? $selector : "</$selector>";
		$position = strpos($this->html, $markup);

		if ($position === false)
		{
			throw new \Exception(\ICanBoogie\format("Unable to locate element with selector %selector", [

				'selector' => $selector

			]));
		}

		$html = &$this->html;
		$html = substr($html, 0, $position) . $fragment . substr($html, $position);
	}

	/**
	 * Replace a placeholder with a HTML fragment.
	 *
	 * @param string $placeholder The placeholder to replace.
	 * @param string $fragment The HTML fragment.
	 */
	public function replace($placeholder, $fragment)
	{
		$this->html = str_replace($placeholder, $fragment, $this->html);
	}
}
