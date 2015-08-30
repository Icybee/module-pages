<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\Block;

use Brickrouge\Document;
use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Text;

use Icybee\Modules\Nodes\Node;
use Icybee\Modules\Pages as Root;
use Icybee\Modules\Pages\Module;
use Icybee\Modules\Pages\Page;
use Icybee\Modules\Pages\PopPage;

/**
 * @property Module $module
 */
class EditBlock extends \Icybee\Modules\Nodes\Block\EditBlock
{
	static protected function add_assets(Document $document)
	{
		parent::add_assets($document);

		$document->css->add(Root\DIR . 'public/edit.css');
		$document->js->add(Root\DIR . 'public/edit.js');
	}

	protected function lazy_get_attributes()
	{
		$app = $this->app;

		return \ICanBoogie\array_merge_recursive(parent::lazy_get_attributes(), [

			Form::HIDDENS => [

				Page::SITEID => $app->site_id,
				Page::LANGUAGE => $app->site->language

			],

			Element::GROUPS => [

				'advanced' => [

					'title' => 'Advanced',
					'weight' => 30

				]

			]

		]);
	}

	protected function lazy_get_children()
	{
		$values = $this->values;
		$nid = $values[Node::NID];
		$is_alone = !$this->module->model->select('nid')->where([ 'siteid' => $this->app->site_id ])->rc;

		list($contents_tags) = $this->module->get_contents_section($values[Node::NID], $values[Page::TEMPLATE]);

		#
		# parentid
		#

		$parentid_el = null;

		if (!$is_alone)
		{
			$parentid_el = new PopPage('select', [

				Form::LABEL => 'parentid',
				Element::OPTIONS_DISABLED => $nid ? [ $nid => true ] : null,
				Element::DESCRIPTION => 'parentid'

			]);
		}

		#
		# location element
		#

		$location_el = null;

		if (!$is_alone)
		{
			$location_el = new PopPage('select', [

				Form::LABEL => 'location',
				Element::GROUP => 'advanced',
				Element::WEIGHT => 10,
				Element::OPTIONS_DISABLED => $nid ? [ $nid => true ] : null,
				Element::DESCRIPTION => 'location'

			]);
		}

		$contents_children = [];

		if (isset($contents_tags[Element::CHILDREN]))
		{
			$contents_children = $contents_tags[Element::CHILDREN];

			unset($contents_tags[Element::CHILDREN]);

			$this->attributes = \ICanBoogie\array_merge_recursive($this->attributes, $contents_tags);
		}

		return array_merge(parent::lazy_get_children(), [

			Page::LABEL => new Text([

				Form::LABEL => 'label',
				Element::DESCRIPTION => 'label'

			]),

			Page::PARENTID => $parentid_el,
			Page::SITEID => null,

			Page::IS_NAVIGATION_EXCLUDED => new Element(Element::TYPE_CHECKBOX, [

				Element::LABEL => 'is_navigation_excluded',
				Element::GROUP => 'visibility',
				Element::DESCRIPTION => 'is_navigation_excluded'

			]),

			Page::PATTERN => new Text([

				Form::LABEL => 'pattern',
				Element::GROUP => 'advanced',
				Element::DESCRIPTION => 'pattern'

			]),

			Page::LOCATIONID => $location_el

		], $contents_children);
	}
}
