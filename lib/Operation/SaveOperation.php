<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\Operation;

use ICanBoogie\Binding\Routing\ForwardUndefinedPropertiesToApplication;
use ICanBoogie\ErrorCollection;
use ICanBoogie\Routing\Pattern;

use Icybee\Binding\Core\PrototypedBindings;
use Icybee\Modules\Files\Module;
use Icybee\Modules\Pages\ContentModel;
use Icybee\Modules\Pages\Page;
use Icybee\Modules\Sites\Binding\CoreBindings as SiteBindings;
use Icybee\Modules\Editor\Binding\CoreBindings as EditorBindings;

/**
 * @property Module $module
 * @property Page $record
 *
 * @inheritdoc
 */
class SaveOperation extends \Icybee\Modules\Nodes\Operation\SaveOperation
{
	use ForwardUndefinedPropertiesToApplication;
	use PrototypedBindings, SiteBindings, EditorBindings;

	/**
	 * For new records, the values for the {@link Page::SITE_ID} and {@link Page::LANGUAGE}
	 * properties are obtained from the current site. If the weight of the page is not defined
	 * it is computed according to the page having the same parent.
	 */
	protected function lazy_get_properties()
	{
		$properties = parent::lazy_get_properties() + [

			Page::PARENT_ID => 0

		];

		if (!$this->key)
		{
			$site = $this->site;
			$site_id = $site->site_id;
			$properties[Page::SITE_ID] = $site_id;
			$properties[Page::LANGUAGE] = $site->language;

			if (empty($properties[Page::WEIGHT]))
			{
				$model = $this->module->model;

				if ($model->count())
				{
					$weight = $model
					->where('site_id = ? AND parent_id = ?', $site_id, $properties[Page::PARENT_ID])
					->maximum('weight');

					$properties[Page::WEIGHT] = ($weight === null) ? 0 : $weight + 1;
				}
				else
				{
					$properties[Page::WEIGHT] = 0;
				}
			}
		}
		else
		{
			unset($properties[Page::UUID]);
			unset($properties[Page::CONSTRUCTOR]);
			unset($properties[Page::CREATED_AT]);
			unset($properties[Page::WEIGHT]);
		}

		return $properties;
	}

	protected function control_form()
	{
		return true;
	}

	/**
	 * For each defined content we check that the corresponding editor is also defined.
	 *
	 * @inheritdoc
	 */
	protected function validate(ErrorCollection $errors)
	{
		$contents = $this->request['contents'];
		$editors = $this->request['editors'];

		if ($contents)
		{
			foreach (array_keys($contents) as $name)
			{
				if (!array_key_exists($name, $editors))
				{
					$errors->add('content', "The editor is missing for the content %name.", [

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
		$url_old = null;

		if ($this->record)
		{
			$record = $this->record;
			$pattern = $record->url_pattern;

			if (!Pattern::is_pattern($pattern))
			{
				$url_old = $pattern;
			}
		}

		$rc = parent::process();
		$nid = $rc['key'];

		#
		# update contents
		#

		/* @var $content_model ContentModel */

		$preserve = [];
		$content_model = $this->module->model('contents');

		$contents = $this->request['contents'];
		$editors = $this->editors;
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
				$editor = $editors[$editor_id];
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

				$content_model->insert([

					'page_id' => $nid,
					'content_id' => $content_id

				] + $values, [

					'on duplicate' => $values

				]);
			}
		}

		#
		# we delete possible remaining content for the page
		#

		$query = $content_model->filter_by_page_id($nid);

		if ($preserve)
		{
			$query->where([ '!content_id' => $preserve ]);
		}

		$query->delete();

		if ($record && $url_old)
		{
			/* @var $record Page */

			$record = $this->module->model[$nid];
			$url_new = $record->url;

			if ($url_new && $url_new != $url_old)
			{
				new Page\MoveEvent($record, $url_old, $url_new);
			}
		}

		return $rc;
	}
}
