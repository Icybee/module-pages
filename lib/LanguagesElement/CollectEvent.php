<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\LanguagesElement;

use ICanBoogie\Event;

use Icybee\Modules\Pages\LanguagesElement;

/**
 * Event class for the `Icybee\Modules\Pages\LanguagesElement::collect` event.
 */
class CollectEvent extends Event
{
	/**
	 * Reference to the languages.
	 *
	 * @var \ICanBoogie\ActiveRecord[string]
	 */
	public $languages;

	/**
	 * The event is constructed with the `render:before` event.
	 *
	 * @param LanguagesElement $target
	 * @param array $payload
	 */
	public function __construct(LanguagesElement $target, array $payload)
	{
		parent::__construct($target, 'collect', $payload);
	}
}
