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

use ICanBoogie\HTTP\AuthenticationRequired;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\ServiceUnavailable;
use ICanBoogie\HTTP\Status;
use ICanBoogie\Prototyped;
use ICanBoogie\Routing\Pattern;

use Icybee\Binding\ControllerBindings;
use Icybee\Modules\Sites\Site;
use Icybee\Modules\Users\User;

/**
 * Page controller.
 *
 * @property-read PageModel $model
 * @property-read User $user
 */
class PageController extends Prototyped
{
	use ControllerBindings;

	/**
	 * @return PageModel
	 */
	protected function get_model()
	{
		return $this->app->models['pages'];
	}

	/**
	 * @return User
	 */
	protected function get_user()
	{
		return $this->app->user;
	}

	public function __invoke(Request $request)
	{
		$request->context->page = $page = $this->resolve_page($request);

		if (!$page)
		{
			return null;
		}

		$response = $page instanceof Response ? $page : $this->render_page($page);

		if ($request->is_head)
		{
			return new Response(null, $response->status, $response->headers);
		}

		return $response;
	}

	/**
	 * Resolve the specified Page and Request into a Response.
	 *
	 * @param Page $page
	 *
	 * @return Response
	 */
	protected function render_page(Page $page)
	{
		$renderer = new PageRenderer;
		$html = $renderer($page);

		return new Response($html, Status::OK, [

			'Content-Type' => 'text/html; charset=utf-8'

		]);
	}

	/**
	 * Resolves a request into a page.
	 *
	 * @param Request $request
	 *
	 * @return Response|Page
	 *
	 * @throws AuthenticationRequired
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	protected function resolve_page(Request $request)
	{
		$this->assert_site_status($request->context->site);

		$path = $request->path;
		$page = $this->model->find_by_path($request->path);

		if (!$page)
		{
			return null;
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

		#
		# Offline pages are displayed if the user has ownership, otherwise an HTTP exception
		# with code 401 (Authentication) is thrown. We add the "✎" marker to the title of the
		# page to indicate that the page is offline but displayed as a preview for the user.
		#

		if (!$page->is_online || $page->site->status != Site::STATUS_OK)
		{
			if (!$this->user->has_ownership($page))
			{
				throw new AuthenticationRequired;
			}

			$page->title .= ' ✎';
		}

		#
		# Update the request with variables extracted from its URI.
		#

		if (isset($page->url_variables))
		{
			$request->path_params = array_merge($request->path_params, $page->url_variables);
			$request->params = array_merge($request->params, $page->url_variables);
		}

		return $page;
	}

	/**
	 * Asserts that the site status is right to display a page.
	 *
	 * @param Site $site
	 *
	 * @throws AuthenticationRequired
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	private function assert_site_status(Site $site)
	{
		if (!$site->siteid)
		{
			throw new NotFound("Unable to find matching website.");
		}

		$status = $site->status;

		switch ($status)
		{
			case Site::STATUS_UNAUTHORIZED: throw new AuthenticationRequired;
			case Site::STATUS_NOT_FOUND: throw new NotFound;
			case Site::STATUS_UNAVAILABLE: throw new ServiceUnavailable;
		}
	}
}
