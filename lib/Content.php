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

use ICanBoogie\ActiveRecord;

use Icybee\Binding\Core\PrototypedBindings;

/**
 * A content of a page.
 *
 * @property-read mixed $rendered The rendered version of the content.
 */
class Content extends ActiveRecord
{
	use PrototypedBindings;

	/**
	 * The identifier of the page the content belongs to.
	 *
	 * @var int
	 */
	public $page_id;

	/**
	 * The identifier of the content.
	 *
	 * @var string
	 */
	public $content_id;

	/**
	 * The content.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * The editor name used to edit and render the content.
	 *
	 * @var string
	 */
	public $editor;

	/**
	 * The rendered version of the content.
	 *
	 * @var string|object
	 */
	private $rendered;

	/**
	 * Returns the rendered contents.
	 *
	 * @return mixed
	 */
	protected function get_rendered()
	{
		return $this->render();
	}

	public function __construct($model='pages/contents')
	{
		parent::__construct($model);
	}

	/**
	 * Renders the content as a string or an object.
	 *
	 * Exceptions thrown during the rendering are caught. The message of the exception is used
	 * as rendered content and the exception is re-thrown.
	 *
	 * @return string|object The rendered content.
	 */
	public function render()
	{
		if ($this->rendered !== null)
		{
			return $this->rendered;
		}

		$editor = $this->app->editors[$this->editor];

		return $this->rendered = $editor->render($editor->unserialize($this->content));
	}

	public function __toString()
	{
		try
		{
			return (string) $this->render();
		}
		catch (\Exception $e)
		{
			return \ICanBoogie\Debug::format_alert($e);
		}
	}
}
