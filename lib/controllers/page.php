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
use ICanBoogie\Event;
use ICanBoogie\Exception;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\ServiceUnavailable;
use ICanBoogie\I18n;
use ICanBoogie\Routing\Pattern;

use Brickrouge\Alert;

use Icybee\Modules\Sites\Site;

define(__NAMESPACE__ . '\PageController\CSS_DOCUMENT_PLACEHOLDER', uniqid());
define(__NAMESPACE__ . '\PageController\JS_DOCUMENT_PLACEHOLDER', uniqid());

class PageController
{
	public function __invoke(Request $request)
	{
		global $core;

		try
		{
			$page = $this->resolve_page($request);

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
			$path = \ICanBoogie\DOCUMENT_ROOT . "protected/all/templates/$code.html";

			if (file_exists($path))
			{
				$template = file_get_contents($path);
				$patron = new \Patron\Engine();
				$page = Page::from
				(
					array
					(
						'siteid' => $core->site_id,
						'title' => I18n\t($e->getCode(), array(), array('scope' => 'exception')),
						'body' => I18n\t($e->getMessage(), array(), array('scope' => 'exception'))
					)
				);

				$request->context->page = $page;

				return new Response($patron($template, $page), $code);
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

		// FIXME: because set() doesn't handle global vars ('$') correctly,
		// we have to set '$page', otherwise a new variable '$page' is created

		$request->context->page = $page;

		#

		new PageController\BeforeRenderEvent // TODO-20130826: target should be 'page'
		(
			$this, array
			(
				'request' => $request,
				'page' => $page
			)
		);

		#
		# The page body is rendered before the template is parsed.
		#

		if ($page->body)
		{
			$page->body->render();
		}

		# template

		$template = $this->resolve_template($page->template);
		$document = $core->document;
		$engine = $this->resolve_engine($template);
		$engine->context['document'] = $document;

		$html = $engine($template, $page, array('file' => $page->template));

		# admin menu

		$admin_menu = (string) new \Icybee\Element\AdminMenu();

		if ($admin_menu)
		{
			$html = str_replace('</body>', $admin_menu . '</body>', $html);
		}

		#

		new PageController\RenderEvent // TODO-20130826: target should be 'page'
		(
			$this, array
			(
				'request' => $request,
				'page' => $page,
				'html' => &$html
			)
		);

		#
		# late replace
		#

		$markup = '<!-- $document.js -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->js . substr($html, $pos + strlen($markup));
		}
		else
		{
			$html = str_replace('</body>', PHP_EOL . PHP_EOL . $document->js . PHP_EOL . '</body>', $html);
		}

		$response = new Response
		(
			$html, 200, array
			(
				'Content-Type' => 'text/html; charset=utf-8'
			)
		);

		return $response;
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
				\ICanBoogie\format("The requested URL does not exists: %uri", array
				(
					'uri' => $request->uri
				))
			);

			case Site::STATUS_UNAVAILABLE: throw new ServiceUnavailable();
		}

		/* /TODO */

		$path = $request->path;
		$page = $core->models['pages']->find_by_path($request->path);

		if (!$page)
		{
			#
			# Page was not found.
			#

			return;
		}

		if ($page->location)
		{
			#
			# The page redirects to another location.
			#

			return new RedirectResponse
			(
				$page->location->url, 301, array
				(
					'Icybee-Redirected-By' => __FILE__ . '::' . __LINE__
				)
			);
		}

		#
		# We make sure that a normalized URL is used. For instance, "/fr" is redirected to
		# "/fr/".
		#

		$url_pattern = Pattern::from($page->url_pattern);

		if (!$url_pattern->params && $page->url != $path)
		{
			$query_string = $request->query_string;

			return new RedirectResponse
			(
				$page->url . ($query_string ? '?' . $query_string : ''), 301, array
				(
					'Icybee-Redirected-By' => __FILE__ . '::' . __LINE__
				)
			);
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
					\ICanBoogie\format('The requested URL %url requires authentication.', array
					(
						'url' => $path
					))
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
			throw new Exception('Unable to resolve path for template: %template', array('%template' => $pathname));
		}

		return file_get_contents($root . $pathname, true);
	}

	protected function resolve_engine($template)
	{
		return new \Patron\Engine;
	}

	static public $nodes = array();

	static public function on_loaded_nodes(\BlueTihi\Context\LoadedNodesEvent $event)
	{
		$nodes = $event->nodes;

		foreach ($nodes as $node)
		{
			if (!$node instanceof \Icybee\Modules\Nodes\Node)
			{
				throw new Exception('Not a node object: \1', array($node));
			}
		}

		self::$nodes = array_merge(self::$nodes, $event->nodes);
	}
}

namespace Icybee\Modules\Pages\PageController;

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
	 * Constructor for the page.
	 *
	 * @var \callable
	 */
	public $constructor;

	/**
	 * Reference to an empty variable that can be altered to put the rendered HTML.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The event is constructed with the type `render:before`.
	 *
	 * @param \Icybee\Modules\Pages\PageController $target
	 * @param array $payload
	 */
	public function __construct(\Icybee\Modules\Pages\PageController $target, array $payload)
	{
		parent::__construct($target, 'render:before', $payload);
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
	public function __construct(\Icybee\Modules\Pages\PageController $target, array $payload)
	{
		parent::__construct($target, 'render', $payload);
	}
}