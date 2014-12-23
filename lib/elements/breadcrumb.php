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

use ICanBoogie\Event;
use Brickrouge\Element;

/**
 * BreadcrumbElement
 * =================
 *
 * Renders a _location_ breadcumb, showing where the page is located in the website hierarchy.
 *
 * A breadcrumb is a navigation aid. It allows users to keep track of their locations within the
 * website. A breadcrumb typically appears horizontally across the top of a web page, usually
 * below title bars or headers. It provides links to the parent pages of the current one. The
 * SINGLE RIGHT-POINTING ANGLE QUOTATION MARK character (›) serves as hierarchy separator.
 *
 * The breadcrumb element is made of slices. In each slice there is a link to the page of the slice
 * unless the slice if the last one in which case the in a strong element.
 *
 * The breadcrumb is an OL element and each of its slice is a LI element.
 *
 *
 * Event: render_inner_html:before
 * -------------------------------
 *
 * Fired before the inner HTML of the element is rendered.
 *
 * ### Signature
 *
 * before_render_inner_html($event, $sender);
 *
 * ### Arguments
 *
 * * event - (ICanBoogie\Event) An event object with the following properties:
 *     * slices - (&array) The slices of the breadcrumb
 *     * separator - (&string) The separator for the slices.
 *     * page - (Icybee\Modules\Pages\Page) The current page object.
 *
 * * target - {@link BreadcrumbElement} The breadcrumb element that fired the event.
 *
 *
 * Event: render_inner_html
 * ------------------------
 *
 * Fired when the inner HTML of the element has been rendered.
 *
 * ### Signature
 *
 * on_render_inner_html($event, $sender);
 *
 * ### Arguments
 *
 * * event - (ICanBoogie\Event) An event object with the following properties:
 *     * rc - (&string) The rendered inner HTML.
 *     * page - (Icybee\Modules\Pages\Page) The current page object.
 *
 * * sender - {@link BreadcrumbElement} The breadcrumb element that fired the event.
 *
 */
class BreadcrumbElement extends Element
{
	const PAGE = '#breadcrumb-page';
	const DIVIDER = '#breadcrumb-divider';

	const DEFAULT_DIVIDER = '›';

	/**
	 * Returns the breadcrumb for the current page.
	 *
	 * The breadcrumb is build and rendered using the #{@link \Brickrouge\Element\Breadcrumb}
	 * element.
	 *
	 * @param array $args
	 * @param \Patron\Engine $patron
	 * @param array|string $template
	 *
	 * @return string
	 */
	static public function markup()
	{
		return new static([

			self::PAGE => \ICanBoogie\app()->request->context->page

		]);
	}

	public function __construct($tags)
	{
		parent::__construct('ol', $tags + [

			self::DIVIDER => self::DEFAULT_DIVIDER,

			'class' => 'breadcrumb'

		]);
	}

	protected function render_inner_html()
	{
		$page = $node = $this[self::PAGE];
		$slices = [];

		while ($node)
		{
			$url = $node->url;
			$label = $node->label;
			$label = \ICanBoogie\shorten($label, 48);
			$label = \Brickrouge\escape($label);

			$slices[] = [

				'url' => $url,
				'label' => $label,
				'class' => $node->css_class('-type -slug -template -constructor -node-id -node-constructor'),
				'page' => $node

			];

			if (!$node->parent && !$node->is_home)
			{
				$node = $node->home;
			}
			else
			{
				$node = $node->parent;
			}
		}

		$slices = array_reverse($slices);
		$divider = $this[self::DIVIDER] ?: self::DEFAULT_DIVIDER;

		new BreadcrumbElement\BeforeRenderInnerHTMLEvent($this, [

			'slices' => &$slices,
			'divider' => &$divider,
			'page' => $page

		]);

		$html = '';
		$slices = array_values($slices);
		$last = count($slices) - 1;

		foreach ($slices as $i => $slice)
		{
			$html .= '<li class="' . $slice['class'] . '">';

			if ($i)
			{
				$html .= '<span class="divider">' . $divider . '</span>';
			}

			$class = \Brickrouge\escape($slice['class']);
			$label = \Brickrouge\escape($slice['label']);

			if ($i != $last)
			{
				$html .= '<a href="' . \Brickrouge\escape($slice['url']) . '" class="' . $class . '">' . $label . '</a>';
			}
			else
			{
				$html .= '<strong class="' . $class . '">' . $label . '</strong>';
			}

			$html .= '</li>';
		}

		new BreadcrumbElement\RenderInnerHTMLEvent($this, [

			'html' => &$html,
			'page' => $page

		]);

		return $html;
	}
}

namespace Icybee\Modules\Pages\BreadcrumbElement;

/**
 * Event class for the `Icybee\Modules\Pages\BreadcrumbElement::render_inner_html:before`
 * event.
 */
class BeforeRenderInnerHTMLEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the slices array.
	 *
	 * @var array
	 */
	public $slices;

	/**
	 * Reference to the divider.
	 *
	 * @var string
	 */
	public $divider;

	/**
	 * The page for which the breadcrumb is computed.
	 *
	 * @var \Icybee\Modules\Pages\Page.
	 */
	public $page;

	/**
	 * The event is constructed with the type `render_inner_html:before`.
	 *
	 * @param \Icybee\Modules\Pages\BreadcrumbElement $target
	 * @param array $payload
	 */
	public function __construct(\Icybee\Modules\Pages\BreadcrumbElement $target, array $payload)
	{
		parent::__construct($target, 'render_inner_html:before', $payload);
	}
}

/**
 * Event class for the `Icybee\Modules\Pages\BreadcrumbElement::render_inner_html`
 * event.
 */
class RenderInnerHTMLEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the inner HTML.
	 *
	 * @var string
	 */
	public $html;

	/**
	 * The page for which the breadcrumb is computed.
	 *
	 * @var \Icybee\Modules\Pages\Page.
	 */
	public $page;

	/**
	 * The event is constructed with the type `render_inner_html`.
	 *
	 * @param \Icybee\Modules\Pages\BreadcrumbElement $target
	 * @param array $payload
	 */
	public function __construct(\Icybee\Modules\Pages\BreadcrumbElement $target, array $payload)
	{
		parent::__construct($target, 'render_inner_html', $payload);
	}
}
