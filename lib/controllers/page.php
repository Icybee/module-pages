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

use ICanBoogie\AuthenticationRequired;
use ICanBoogie\Exception;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\ServiceUnavailable;
use ICanBoogie\I18n;
use ICanBoogie\Routing\Pattern;

use Icybee\Modules\Sites\Site;

define(__NAMESPACE__ . '\PageController\CSS_DOCUMENT_PLACEHOLDER', uniqid());
define(__NAMESPACE__ . '\PageController\JS_DOCUMENT_PLACEHOLDER', uniqid());

class PageController
{
	const DOCUMENT_JS_PLACEHOLDER = '<!-- $document.js -->';

	public function __invoke(Request $request)
	{
		global $core;

		try
		{
			$request->context->page = $page = $this->resolve_page($request);

			if (!$page)
			{
				return;
			}

			if ($page instanceof Response)
			{
				return $page;
			}

			return $this->resolve_response($page, $request);
		}
		catch (\Exception $e) // TODO-20130812: This shouldn't be handled by the class, but by Icybee or the user.
		{
			$code = $e->getCode();
			$pathname = \ICanBoogie\DOCUMENT_ROOT . "protected/all/templates/$code.html";

			if (file_exists($pathname))
			{
				$request->context->page = $page = Page::from([

					'siteid' => $core->site_id,
					'title' => I18n\t($e->getCode(), [], [ 'scope' => 'exception' ]),
					'body' => I18n\t($e->getMessage(), [], [ 'scope' => 'exception' ])

				]);

				$template = file_get_contents($pathname);
				$engine = $this->resolve_engine($template);

				return new Response($engine($template, $page), $code);
			}

			throw $e;
		}
	}

	/**
	 * Resolve the specified Page and Request into a Response.
	 *
	 * @param Page $page
	 * @param Request $request
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	protected function resolve_response(Page $page, Request $request)
	{
		global $core;

		$template = $this->resolve_template($page->template);
		$document = $core->document;
		$engine = $this->resolve_engine($template);
		$engine->context['page'] = $page;
		$engine->context['document'] = $document;

		new PageController\BeforeRenderEvent($this, $request, $page, $engine->context);

		#
		# The page body is rendered before the template is parsed.
		#

		if ($page->body && is_callable(array($page->body, 'render')))
		{
			$page->body->render();
		}

		$html = $engine($template, $page, [ 'file' => $page->template ]);

		new PageController\RenderEvent($this, $request, $page, $html);

		#
		# late replace
		#

		$pos = strpos($html, self::DOCUMENT_JS_PLACEHOLDER);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos)
			. $document->js
			. substr($html, $pos + strlen(self::DOCUMENT_JS_PLACEHOLDER));
		}
		else
		{
			$html = str_replace('</body>', PHP_EOL . PHP_EOL . $document->js . PHP_EOL . '</body>', $html);
		}

		return new Response($html, 200, [

			'Content-Type' => 'text/html; charset=utf-8'

		]);
	}

	/**
	 * Resolves a request into a page.
	 *
	 * @param Request $request
	 *
	 * @return Page|Response
	 */
	protected function resolve_page(Request $request)
	{
		global $core;

		/* TODO-20130812: Move the following code section in the Sites module. */

		$site = $request->context->site;

		if (!$site->siteid)
		{
			throw new NotFound('Unable to find matching website.');
		}

		$status = $site->status;

		switch ($status)
		{
			case Site::STATUS_UNAUTHORIZED: throw new AuthenticationRequired();
			case Site::STATUS_NOT_FOUND: throw new NotFound
			(
				\ICanBoogie\format("The requested URL does not exists: %uri", [

					'uri' => $request->uri

				])
			);

			case Site::STATUS_UNAVAILABLE: throw new ServiceUnavailable();
		}

		/* /TODO */

		$path = $request->path;
		$page = $core->models['pages']->find_by_path($request->path);

		if (!$page)
		{
			return;
		}

		if ($page->location)
		{
			return new RedirectResponse($page->location->url, 301, [

				'Icybee-Redirected-By' => __FILE__ . '::' . __LINE__

			]);
		}

		#
		# We make sure that a normalized URL is used. For instance, "/fr" is redirected to
		# "/fr/".
		#

		$url_pattern = Pattern::from($page->url_pattern);

		if (!$url_pattern->params && $page->url != $path)
		{
			$query_string = $request->query_string;

			return new RedirectResponse($page->url . ($query_string ? '?' . $query_string : ''), 301, [

				'Icybee-Redirected-By' => __FILE__ . '::' . __LINE__

			]);
		}

		if (!$page->is_online || $page->site->status != Site::STATUS_OK)
		{
			#
			# Offline pages are displayed if the user has ownership, otherwise an HTTP exception
			# with code 401 (Authentication) is thrown. We add the "✎" marker to the title of the
			# page to indicate that the page is offline but displayed as a preview for the user.
			#

			if (!$core->user->has_ownership('pages', $page))
			{
				throw new AuthenticationRequired
				(
					\ICanBoogie\format('The requested URL %url requires authentication.', [

						'url' => $path

					])
				);
			}

			$page->title .= ' ✎';
		}

		if (isset($page->url_variables))
		{
			$request->path_params = array_merge($request->path_params, $page->url_variables);
			$request->params = array_merge($request->params, $page->url_variables);
		}

		return $page;
	}

	protected function resolve_template($name)
	{
		global $core;

		$root = \ICanBoogie\DOCUMENT_ROOT;
		$pathname = $core->site->resolve_path('templates/' . $name);

		if (!$pathname)
		{
			throw new Exception('Unable to resolve path for template: %template', [ '%template' => $pathname ]);
		}

		return file_get_contents($root . $pathname, true);
	}

	protected function resolve_engine($template)
	{
		return new \Patron\Engine;
	}
}

namespace Icybee\Modules\Pages\PageController;

use ICanBoogie\HTTP\Request;

use Icybee\Modules\Pages\Page;

/**
 * Event class for the 'Icybee\Modules\Pages\PageController::render:before'.
 */
class BeforeRenderEvent extends \ICanBoogie\Event
{
	/**
	 * Request.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * Response.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * Rendering context.
	 *
	 * @var mixed
	 */
	public $context;

	/**
	 * The event is constructed with the type `render:before`.
	 *
	 * @param \Icybee\Modules\Pages\PageController $target
	 * @param array $payload
	 */
	public function __construct(\Icybee\Modules\Pages\PageController $target, Request $request, Page $page, &$context)
	{
		$this->request = $request;
		$this->page = $page;
		$this->context = &$context;

		parent::__construct($target, 'render:before');
	}
}

/**
 * Event class for the `Icybee\Modules\Pages\PageController::render` event.
 */
class RenderEvent extends \ICanBoogie\Event
{
	/**
	 * The request.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The page being rendered.
	 *
	 * @var \Icybee\Modules\Pages\Page
	 */
	public $page;

	/**
	 * The rendered HTML.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The event is constructed with the type `render`.
	 *
	 * @param \Icybee\Modules\Pages\PageController $target
	 * @param array $payload
	 */
	public function __construct(\Icybee\Modules\Pages\PageController $target, Request $request, Page $page, &$html)
	{
		$this->request = $request;
		$this->page = $page;
		$this->html = &$html;

		parent::__construct($target, 'render');
	}
}