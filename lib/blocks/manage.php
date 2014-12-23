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

use ICanBoogie\ActiveRecord\Query;

use Icybee\ManageBlock\Options;
use Brickrouge\Element;
use Brickrouge\Button;

class ManageBlock extends \Icybee\Modules\Nodes\ManageBlock
{
	static protected function add_assets(\Brickrouge\Document $document)
	{
		parent::add_assets($document);

		$document->css->add('manage.css');
		$document->js->add('manage.js');
	}

	public function __construct(Module $module, array $attributes=[])
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
	 * - `is_navigation_excluded`: An instance of {@link ManageBlock\IsNavigationExcluded}.
	 *
	 * @inheritdoc
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), [

			'title' => __CLASS__ . '\TitleColumn',
			'url' => __CLASS__ . '\URLColumn',
			'is_navigation_excluded' => __CLASS__ . '\IsNavigationExcluded'

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

		$expanded = array_flip($this->options->expanded);

		return $query->model->blueprint($this->app->site_id)->subset(null, null, function(BlueprintNode $node) use($expanded) {

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

namespace Icybee\Modules\Pages\ManageBlock;

use ICanBoogie\Routing\Pattern;

use Brickrouge\Element;
use Brickrouge\Text;

use Icybee\ManageBlock\Column;
use Icybee\ManageBlock\BooleanColumn;
use Icybee\Modules\Nodes\ManageBlock\EditDecorator;

class TitleColumn extends \Icybee\Modules\Nodes\ManageBlock\TitleColumn
{
	public function render_cell($record)
	{
		$rc = '';

		if ($this->manager->mode == 'tree')
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $record->depth);
			$rc .= '<div class="handle"><i class="icon-move"></i></div>';

			if (0)
			{
				$rc .= new Text([

					Element::LABEL => 'w',
					Element::LABEL_POSITION => 'before',
					'name' => 'weights[' . $record->nid . ']',
					'value' => $record->weight,
					'size' => 3,
					'style' => 'border: none; background: transparent; color: green'

				]);

				$rc .= '&nbsp;';

				$rc .= new Text([

					Element::LABEL => 'p',
					Element::LABEL_POSITION => 'before',
					'name' => 'parents[' . $record->nid . ']',
					'value' => $record->parentid,
					'size' => 3,
					'style' => 'border: none; background: transparent; color: green'

				]);
			}
			else
			{
				$rc .= new Element('input', [

					'name' => 'parents[' . $record->nid . ']',
					'type' => 'hidden',
					'value' => $record->parentid

				]);
			}
		}

		$rc .= new EditDecorator($record->label, $record);

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $record->nid . '</small>';
		}

		if ($this->manager->mode == 'tree' && $record->has_child)
		{
			$expanded = in_array($record->nid, $this->manager->options->expanded);

			$rc .= ' <a class="treetoggle" href="?' . ($expanded ? 'collapse' : 'expand') . '=' . $record->nid . '">' . ($expanded ? '-' : "+{$record->descendants_count}") . '</a>';
		}

		#
		# updated_at
		#

		$now = time();
		$updated_at = strtotime($record->updated_at);

		if ($now - $updated_at < 7200)
		{
			$rc .= ' <sup style="vertical-align: text-top; color: red;">Récemment modifié</sup>';
		}

		return $rc;
	}
}

/**
 * Representation of the `url` column.
 */
class URLColumn extends \Icybee\Modules\Nodes\ManageBlock\URLColumn
{
	/**
	 * @param \Icybee\Modules\Pages\Page $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		$t = $this->manager->t;
		$options = $this->manager->options;
		$pattern = $record->url_pattern;

		if ($options->search || $options->filters)
		{
			if (Pattern::is_pattern($pattern))
			{
				return;
			}

			$url = $record->url;

			// DIRTY-20100507

			if ($record->location)
			{
				$location = $record->location;
				$title = $t('This page is redirected to: !title (!url)', [

					'!title' => $location->title,
					'!url' => $location->url

				]);

				return <<<EOT
<span class="small">
<i class="icon-mail-forward" title="$title"></i>
<a href="$url">$url</a>
</span>
EOT;
			}

			return <<<EOT
<span class="small"><a href="$url">$url</a></span>
EOT;
		}

		$rc = '';
		$location = $record->location;

		if ($location)
		{
			$rc .= '<span class="icon-mail-forward" title="' . $t('This page is redirected to: !title (!url)', [

				'!title' => $location->title,
				'!url' => $location->url

			]) . '"></span>';
		}
		else if (!Pattern::is_pattern($pattern))
		{
			$url = (\ICanBoogie\app()->site_id == $record->siteid) ? $record->url : $record->absolute_url;

			$title = $t('Go to the page: !url', [ '!url' => $url ]);

			$rc .= '<a href="' . $url . '" title="' . $title . '" target="_blank"><i class="icon-external-link"></i></a>';
		}

		return $rc;
	}
}

/**
 * Representation of the `is_navigation_excluded` column.
 */
class IsNavigationExcluded extends BooleanColumn
{
	public function __construct(\Icybee\ManageBlock $manager, $id, array $options=[])
	{
		parent::__construct($manager, $id, $options + [

			'title' => null,
			'filters' => [

				'options' => [

					'=1' => 'Excluded from navigation',
					'=0' => 'Included in navigation'

				]

			]

		]);
	}

	public function render_cell($record)
	{
		return new Element('i', [

			'class' => 'icon-sitemap trigger ' . ($record->is_navigation_excluded ? 'on' : ''),
			'data-nid' => $record->nid,
			'title' => "Inclure ou exclure la page du menu de navigation principal"

		]);
	}
}
