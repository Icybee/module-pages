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

use ICanBoogie\HTTP\Request;

use Icybee\Modules\Pages\PageRenderer\BeforeRenderEvent;
use Icybee\Modules\Pages\PageRenderer\RenderEvent;

/**
 * Render a {@link Page} instance into an HTML string.
 */
class PageRenderer
{
	use \ICanBoogie\GetterTrait;

	public function __invoke(Page $page)
	{
		global $core;

		$template_pathname = $this->resolve_template_pathname($page->template);
		$template = file_get_contents($template_pathname);
		$document = $core->document;
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
		global $core;

		$root = \ICanBoogie\DOCUMENT_ROOT;
		$pathname = $core->site->resolve_path('templates/' . $name);

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
		return new \Patron\Engine;
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
	 * @param Page $page The page being rendered.
	 * @param string $html Reference to the rendered HTML.
	 */
	public function __construct(PageRenderer $target, &$html, Page $page, Document $document)
	{
		$this->html = &$html;
		$this->page = $page;
		$this->document = $document;

		parent::__construct($target, 'render');
	}
}