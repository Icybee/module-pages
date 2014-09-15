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
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\ServiceUnavailable;
use ICanBoogie\I18n;
use ICanBoogie\Routing\Pattern;

use Icybee\Modules\Sites\Site;

class PageController
{
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

			return $this->render_page($page);
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

				return new Response($engine($template, $page, [ 'file' => $pathname ]), $code);
			}

			throw $e;
		}
	}

	/**
	 * Resolve the specified Page and Request into a Response.
	 *
	 * @param Page $page
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	protected function render_page(Page $page)
	{
		$renderer = new PageRenderer;
		$html = $renderer($page);

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

	protected function resolve_engine($template)
	{
		return new \Patron\Engine;
	}
}