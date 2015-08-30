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

use ICanBoogie\ActiveRecord\Query;

use Brickrouge\Button;
use Brickrouge\Document;
use Brickrouge\Element;

use Icybee\Block\ManageBlock\Options;
use Icybee\Modules\Pages\BlueprintNode;
use Icybee\Modules\Pages\Module;
use Icybee\Modules\Pages\PageModel;

class ManageBlock extends \Icybee\Modules\Nodes\Block\ManageBlock
{
	static protected function add_assets(Document $document)
	{
		parent::add_assets($document);

		$document->css->add(__DIR__ . '/ManageBlock.css');
		$document->js->add(__DIR__ . '/ManageBlock.js');
	}

	public function __construct(Module $module, array $attributes = [])
	{
		parent::__construct($module, $attributes + [

			self::T_COLUMNS_ORDER => [

				'title', 'url', 'is_navigation_excluded', 'is_online', 'uid', 'updated_at'

			],

			self::T_ORDER_BY => null

		]);
	}

	/**
	 * Adds the following columns:
	 *
	 * - `title`: An instance of {@link ManageBlock\TitleColumn}.
	 * - `url`: An instance of {@link ManageBlock\URLColumn}.
	 * - `is_navigation_excluded`: An instance of {@link ManageBlock\IsNavigationExcludedColumn}.
	 *
	 * @inheritdoc
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), [

			'title' => ManageBlock\TitleColumn::class,
			'url' => ManageBlock\URLColumn::class,
			'is_navigation_excluded' => ManageBlock\IsNavigationExcludedColumn::class

		]);
	}

	/**
	 * Adds the following jobs:
	 *
	 * - `copy`: Copy the selected nodes.
	 *
	 * @inheritdoc
	 */
	protected function get_available_jobs()
	{
		return array_merge(parent::get_available_jobs(), [

			'copy' => 'Copier'

		]);
	}

	protected function render_jobs(array $jobs)
	{
		return parent::render_jobs($jobs) .

		new Element('div', [

			Element::IS => 'ActionBarUpdateTree',

			Element::CHILDREN => [

				'label' => new Element('label', [

					Element::INNER_HTML => $this->t("The page tree has been changed"),

					'class' => 'btn-group-label'

				]),

				'save' => new Button("Save", [ 'class' => 'btn-primary' ]),
				'reset' => new Button("Reset", [ 'data-dismiss' => 'changes' ])

			],

			'class' => 'actionbar-actions actionbar-actions--update-tree'

		]);
	}

	protected $mode = 'tree';

	protected function get_mode()
	{
		return $this->mode;
	}

	protected $expand_highlight;

	/**
	 * Overrides the method to add support for expanded tree nodes.
	 *
	 * The methods adds the `expanded` option which is used to store expanded tree nodes. The
	 * option is initialized with first level pages.
	 *
	 * @inheritdoc
	 */
	protected function update_options(Options $options, array $modifiers)
	{
		$options = parent::update_options($options, $modifiers);

		if (!isset($options->expanded))
		{
			$options->expanded = [];
		}

		if (isset($modifiers['expand']) || isset($modifiers['collapse']))
		{
			$expanded = array_flip($options->expanded);

			if (isset($modifiers['expand']))
			{
				$nid = $this->expand_highlight = filter_var($modifiers['expand'], FILTER_VALIDATE_INT);
				$expanded[$nid] = true;
			}

			if (isset($modifiers['collapse']))
			{
				unset($expanded[filter_var($modifiers['collapse'], FILTER_VALIDATE_INT)]);
			}

			$options->expanded = array_keys($expanded);
		}

		if ($options->order_by == 'title')
		{
			$options->order_by = null;
		}

		if ($options->filters || $options->order_by || $options->search)
		{
			$this->mode = 'flat';
		}

		return $options;
	}

	/**
	 * Fetches the records according to the query and the display mode.
	 *
	 * The method is overrode if the display mode is `tree` in which case the records are fetched
	 * according to their relation and the _expand_ state of their parent.
	 *
	 * @inheritdoc
	 */
	protected function fetch_records(Query $query)
	{
		if ($this->mode !== 'tree')
		{
			return parent::fetch_records($query);
		}

		/* @var $model PageModel */

		$expanded = array_flip($this->options->expanded);
		$model = $query->model;

		return $model->blueprint($this->app->site_id)->subset(null, null, function(BlueprintNode $node) use($expanded) {

			return !(!$node->parentid || isset($expanded[$node->parentid]));

		})->ordered_records;
	}

	/**
	 * Replaces the limiter by a simple count if the records are displayed as a tree.
	 *
	 * @inheritdoc
	 */
	protected function render_controls()
	{
		if ($this->mode !== 'tree')
		{
			return parent::render_controls();
		}

		$count = $this->t(':count pages', [ ':count' => $this->count ]);

		# A `SELECT` element is added to have the same height as the jobs element.

		return <<<EOT
<div class="listview-controls">
	$count
</div>
EOT;
	}

	protected function render_rows(array $rows)
	{
		$view_ids = $this->module->model('contents')
		->select('pageid, content')
		->where('contentid = "body" AND editor = "view"')
		->pairs;

		$rendered_rows = parent::render_rows($rows);
		$records = array_values($this->records);

		foreach ($rendered_rows as $i => $row)
		{
			$row->add_class('entry');
			$row->add_class('draggable');

			$record = $records[$i];
			$nid = $record->nid;

			if ($this->expand_highlight && $record->parentid == $this->expand_highlight)
			{
				$row->add_class('volatile-highlight');
			}

			if (isset($view_ids[$nid]))
			{
				$row->add_class('view');
			}

			if ($record->pattern)
			{
				$row->add_class('pattern');
			}

			if ($record->locationid)
			{
				$row->add_class('location');
			}

			$row['id'] = "nid:{$nid}"; // TODO-20130627: deprecate this, or use 'data-nid' or maybe move this to manager with a data-key on the TR.
		}

		return $rendered_rows;
	}
}
