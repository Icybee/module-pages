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
use ICanBoogie\Routing\Pattern;

class SaveOperation extends \Icybee\Modules\Nodes\SaveOperation
{
	/**
	 * For new records, the values for the {@link Page::SITEID} and {@link Page::LANGUAGE}
	 * properties are obtained from the current site. If the weight of the page is not defined
	 * it is computed according to the page having the same parent.
	 */
	protected function lazy_get_properties()
	{
		$properties = parent::lazy_get_properties() + [

			Page::PARENTID => 0

		];

		if (!$this->key)
		{
			/* @var $site \Icybee\Modules\Sites\Site */

			$site = $this->app->site;
			$siteid = $site->siteid;
			$properties[Page::SITEID] = $siteid;
			$properties[Page::LANGUAGE] = $site->language;

			if (empty($properties[Page::WEIGHT]))
			{
				$model = $this->module->model;

				if ($model->count())
				{
					$weight = $model
					->where('siteid = ? AND parentid = ?', $siteid, $properties[Page::PARENTID])
					->maximum('weight');

					$properties[Page::WEIGHT] = ($weight === null) ? 0 : $weight + 1;
				}
				else
				{
					$properties[Page::WEIGHT] = 0;
				}
			}
		}

		return $properties;
	}

	/**
	 * For each defined content we check that the corresponding editor is also defined.
	 */
	protected function validate(\ICanBoogie\Errors $errors)
	{
		$contents = $this->request['contents'];
		$editors = $this->request['editors'];

		if ($contents)
		{
			foreach (array_keys($contents) as $name)
			{
				if (!array_key_exists($name, $editors))
				{
					$errors['content'][] = $errors->format('The editor is missing for the content %name.', [

						'name' => $name

					]);
				}
			}
		}

		return parent::validate($errors);
	}

	protected function process()
	{
		$record = null;
		$oldurl = null;

		if ($this->record)
		{
			$record = $this->record;
			$pattern = $record->url_pattern;

			if (!Pattern::is_pattern($pattern))
			{
				$oldurl = $pattern;
			}
		}

		$rc = parent::process();
		$nid = $rc['key'];

		#
		# update contents
		#

		/* var $contents_model ContentModel */

		$preserve = [];
		$contents_model = $this->module->model('contents');

		$contents = $this->request['contents'];
		$editor_ids = $this->request['editors'];

		if ($contents && $editor_ids)
		{
			foreach ($contents as $content_id => $unserialized_content)
			{
				if (!$unserialized_content)
				{
					continue;
				}

				$editor_id = $editor_ids[$content_id];
				$editor = $this->app->editors[$editor_id];
				$content = $editor->serialize($unserialized_content);

				if (!$content)
				{
					continue;
				}

				$preserve[$content_id] = $content_id;

				$values = [

					'content' => $content,
					'editor' => $editor_id

				];

				$contents_model->insert([

					'pageid' => $nid,
					'contentid' => $content_id

				] + $values, [

					'on duplicate' => $values

				]);
			}
		}

		#
		# we delete possible remaining content for the page
		#

		$arr = $contents_model->filter_by_pageid($nid);

		if ($preserve)
		{
			$arr->where([ '!contentid' => $preserve ]);
		}

		$arr->delete();

		if ($record && $oldurl)
		{
			$record = $this->module->model[$nid];
			$newurl = $record->url;

			if ($newurl && $newurl != $oldurl)
			{
				new Page\MoveEvent($record, $oldurl, $newurl);
			}
		}

		return $rc;
	}
}

namespace Icybee\Modules\Pages\Page;

/**
 * Event class for the `Icybee\Modules\Pages\Page::move` event.
 */
class MoveEvent extends \ICanBoogie\Event
{
	/**
	 * Previous path.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * New path.
	 *
	 * @var string
	 */
	public $to;

	/**
	 * The event is constructed with the type `move`.
	 *
	 * @param \Icybee\Modules\Pages\Page $target
	 * @param string $from Previous path.
	 * @param string $to New path.
	 */
	public function __construct(\Icybee\Modules\Pages\Page $target, $from, $to)
	{
		$this->from = $from;
		$this->to = $to;

		parent::__construct($target, 'move');
	}
}
