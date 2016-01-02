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

use ICanBoogie\Binding\HTTP\AbstractDispatcherConstructor;

/**
 * Construct a {@link PageDispatcher} instance.
 */
class PageDispatcherConstructor extends AbstractDispatcherConstructor
{
	/**
	 * @inheritdoc
	 *
	 * @return PageDispatcher
	 */
	public function __invoke(array $config)
	{
		return new PageDispatcher;
	}
}
