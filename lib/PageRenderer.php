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
use ICanBoogie\Render\EngineCollection;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Render\TemplateResolver;

use Brickrouge\Document;

use Icybee\Binding\ObjectBindings;
use Icybee\Modules\Pages\PageRenderer\BeforeRenderEvent;
use Icybee\Modules\Pages\PageRenderer\RenderEvent;

/**
 * Render a {@link Page} instance into an HTML string.
 *
 * @property-read Document $document
 * @property-read TemplateResolver $template_resolver
 * @property-read EngineCollection $template_engines
 * @property-read array $template_extensions
 */
class PageRenderer
{
	use AccessorTrait;
	use ObjectBindings;

	protected function get_app()
	{
		return \ICanBoogie\app();
	}

	protected function get_document()
	{
		return $this->app->document;
	}

	protected function get_template_resolver()
	{
		return $this->app->template_resolver;
	}

	protected function get_template_engines()
	{
		return $this->app->template_engines;
	}

	protected function get_template_extensions()
	{
		return $this->app->template_engines->extensions;
	}

	/**
	 * Renders a page.
	 *
	 * @param Page $page
	 *
	 * @return string
	 */
	public function __invoke(Page $page)
	{
		$template_pathname = $this->resolve_template_pathname($page->template);
		$engine = $this->resolve_engine($template_pathname);
		$document = $this->document;
		$context = [

			'page' => $page,
			'document' => $document

		];

		$user_startup = \ICanBoogie\DOCUMENT_ROOT . 'user-startup.php';

		if (file_exists($user_startup))
		{
			require $user_startup;
		}

		new BeforeRenderEvent($this, $page, $document, $context);

		#
		# The page body is rendered before the template is parsed.
		#

		if ($page->body && is_callable([ $page->body, 'render' ]))
		{
			$page->body->render();
		}

		# template

		$html = $engine->render($template_pathname, $page, $context);

		new RenderEvent($this, $html, $page, $document);

		return $html;
	}

	/**
	 * Resolves the template pathname.
	 *
	 * @param string $name
	 *
	 * @return string
	 *
	 * @throw TemplateNotFound if the template cannot be resolved
	 */
	public function resolve_template_pathname($name)
	{
		$tried = [];
		$template_pathname = $this->template_resolver->resolve($name, $this->template_extensions, $tried);

		if (!$template_pathname)
		{
			throw new TemplateNotFound("Unable to find template for: $name.", $tried);
		}

		return $template_pathname;
	}

	/**
	 * Resolves the template engine to use with a template.
	 *
	 * @param string $template_pathname
	 *
	 * @return \ICanBoogie\Render\Engine
	 */
	protected function resolve_engine($template_pathname)
	{
		return $this->template_engines->resolve_engine($template_pathname);
	}
}
