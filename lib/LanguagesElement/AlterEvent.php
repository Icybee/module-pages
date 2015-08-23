<?php

namespace Icybee\Modules\Pages\LanguagesElement;

use ICanBoogie\Event;

use Icybee\Modules\Pages\LanguagesElement;

/**
 * Event class for the `Icybee\Modules\Pages\LanguagesElement::alter` event.
 */
class AlterEvent extends Event
{
	/**
	 * Reference to the links array.
	 *
	 * @var \Brickrouge\Element[string]
	 */
	public $links;

	/**
	 * Reference to the language records.
	 *
	 * @var \ICanBoogie\ActiveRecord[string]
	 */
	public $languages;

	/**
	 * The current page.
	 *
	 * @var \Icybee\Modules\Pages\Page
	 */
	public $page;

	/**
	 * The event is constructed with the `alter` event.
	 *
	 * @param LanguagesElement $target
	 * @param array $payload
	 */
	public function __construct(LanguagesElement $target, array $payload)
	{
		parent::__construct($target, 'alter', $payload);
	}
}
