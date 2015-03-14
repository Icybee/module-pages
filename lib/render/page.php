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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\HTTP\Request;

use Icybee\Modules\Pages\PageRenderer\BeforeRenderEvent;
use Icybee\Modules\Pages\PageRenderer\RenderEvent;

/**
 * Render a {@link Page} instance into an HTML string.
 */
class PageRenderer
{
	use AccessorTrait;

	public function __invoke(Page $page)
	{
		$app = \ICanBoogie\app();
		$document = $app->document;

		$template_pathname = $this->resolve_template_pathname($page->template);
		$template = file_get_contents($template_pathname);
		$engine = $this->resolve_engine($template);
		$engine->context['page'] = $page;
		$engine->context['document'] = $document;

		$user_startup = \ICanBoogie\DOCUMENT_ROOT . 'user-startup.php';

		if (file_exists($user_startup))
		{
			require $user_startup;
		}

		new BeforeRenderEvent($this, $page, $document, $engine->context);

		#
		# The page body is rendered before the template is parsed.
		#

		if ($page->body && is_callable([ $page->body, 'render' ]))
		{
			$page->body->render();
		}

		# template

		$html = $engine($template, $page, [ 'file' => $template_pathname ]);

		new RenderEvent($this, $html, $page, $document);

		return $html;
	}

	protected function resolve_template_pathname($name)
	{
		$root = \ICanBoogie\DOCUMENT_ROOT;
		$pathname = \ICanBoogie\app()->site->resolve_path('templates/' . $name);

		if (!$pathname)
		{
			throw new \Exception(\ICanBoogie\format('Unable to resolve path for template: %name', [

				'%name' => $name

			]));
		}

		return $root . $pathname;
	}

	protected function resolve_engine($template)
	{
		return \Patron\get_patron();
	}
}

namespace Icybee\Modules\Pages\PageRenderer;

use ICanBoogie\HTTP\Request;

use Brickrouge\Document;

use Icybee\Modules\Pages\Page;
use Icybee\Modules\Pages\PageRenderer;

/**
 * Event class for the 'Icybee\Modules\Pages\PageRenderer::render:before'.
 *
 * Third parties may use this event to alter the context of the rendering.
 */
class BeforeRenderEvent extends \ICanBoogie\Event
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
	 * @var mixed
	 */
	public $context;

	/**
	 * The event is constructed with the type `render:before`.
	 *
	 * @param PageRenderer $target
	 * @param Page $page
	 * @param Document $document
	 * @param mixed $context
	 */
	public function __construct(PageRenderer $target, Page $page, Document $document, &$context)
	{
		$this->page = $page;
		$this->document = $document;
		$this->context = &$context;

		parent::__construct($target, 'render:before');
	}
}

/**
 * Event class for the `Icybee\Modules\Pages\PageRenderer::render` event.
 *
 * Third parties may use this event to alter the renderer HTML.
 */
class RenderEvent extends \ICanBoogie\Event
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
	public function inject($fragment, $selector, $where='bottom')
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
