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

use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Dispatches managed pages.
 */
class PageDispatcher implements Dispatcher
{
	/**
	 * @inheritdoc
	 */
	public function __invoke(Request $request)
	{
		$controller = new PageController;
		$response = $controller($request);

		if (!$response)
		{
			return null;
		}

		if (!($response instanceof Response))
		{
			$response = new Response($response);
		}

		$response->cache_control = 'private, no-cache, no-store, must-revalidate';

		return $response;
	}

	/**
	 * @inheritdoc
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		throw $exception;
	}
}
