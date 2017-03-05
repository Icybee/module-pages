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

use ICanBoogie\I18n;

use Brickrouge\Alert;
use Brickrouge\Element;
use Brickrouge\Form;

use ICanBoogie\Render\TemplateNotFound;
use Icybee\Element\Section;
use Icybee\Modules\Editor\EditorElement;
use Icybee\Modules\Editor\MultiEditorElement;

use Patron\HTMLParser;
use Patron\Engine as Patron;

/**
 * @property-read \ICanBoogie\Application
 * @property-read PageModel $model
 */
class Module extends \Icybee\Modules\Nodes\Module
{
	public function get_contents_section($nid, $template = null)
	{
		list($template, $template_description, $is_inherited) = $this->resolve_template($nid, $template);
		list($elements, $hiddens) = $this->get_contents_section_elements($nid, $template);

		if ($elements)
		{
			$template_description .= ' ' . $this->app->translate("The following elements are editable:");
		}
		else
		{
			$template_description = $this->app->translate("The <q>:template</q> template does not define any editable element.", [ ':template' => $template ]);
		}

		$elements = array_merge([

			Page::TEMPLATE => new PopTemplate([

				Element::GROUP => 'contents',
				Element::DESCRIPTION => $template_description

			])

		], $elements);

		return [

			[
				Form::HIDDENS => $hiddens,

				#
				# If the template is inherited, we remove the value in order to have a clean
				# inheritance, easier to manage.
				#

				Form::VALUES => [

					Page::TEMPLATE => $is_inherited ? null : $template

				],

				Element::GROUPS => [

					'contents' => [

						'title' => 'Template',
						'weight' => 10

					],

					'contents.inherit' => [

						'weight' => 11,
						'description' => 'contents.inherit'

					]

				],

				Element::CHILDREN => $elements

			],

			[

				'name' => $template,
				'description' => $template_description,
				'inherited' => $is_inherited

			]

		];
	}

	protected function get_contents_section_elements($nid, $template)
	{
		$info = self::get_template_info($template);

		if (!$info)
		{
			return [ [], [] ];
		}

		list($editables, $styles) = $info;

		$elements = [];
		$hiddens = [];

		$app = $this->app;
		$contents_model = $this->model('contents');
		$context = $app->site->path;

		foreach ($editables as $editable)
		{
			$id = $editable['id'];
			$title = $editable['title'];
			$title = $app->translate($id, [], [ 'scope' => [ 'content', 'title' ], 'default' => $title ]);

			$does_inherit = !empty($editable['inherit']);

			$value = null;

			$editor_id = $editable['editor'];
			$editor_config = json_decode($editable['config'], true);
			$editor_description = $editable['description'];

			#
			#
			#

			$contents = $nid ? $contents_model->where('page_id = ? AND content_id = ?', $nid, $id)->one : null;

			if ($contents)
			{
				$value = $contents->content;

				if (!$editor_id)
				{
					$editor_id = $contents->editor;
				}
			}

			if ($does_inherit)
			{
				if (!$contents && $nid)
				{
					$inherited = null;
					$node = $this->model[$nid];

					while ($node)
					{
						$node_contents = $node->contents;

						if (isset($node_contents[$id]))
						{
							$inherited = $node;

							break;
						}

						$node = $node->parent;
					}

					if (!$node)
					{
						$node = $app->site->home;

						if (isset($node->contents[$id]))
						{
							$inherited = $node;
						}
					}

					// TODO-20101214: check home page

					if ($inherited)
					{
						$elements[] = new Element('div', [

							Form::LABEL => $title,
							Element::GROUP => 'contents.inherit',
							Element::INNER_HTML => '',
							Element::DESCRIPTION => $app->translate
							(
								'This content is currently inherited from the <q><a href="!url">!title</a></q> parent page. <a href="#edit" class="btn">Edit the content</a>', array
								(
									'!url' => $context . '/admin/' . $this->id . '/' . $inherited->nid . '/edit',
									'!title' => $inherited->title
								)
							),

							Section::T_PANEL_CLASS => 'inherit-toggle'

						]);
					}
					else
					{
						$editor_description .= $app->translate('No parent page define this content.');
					}
				}
			}

			/*
			 * each editor as a base name `contents[<editable_id>]` and much at least define two
			 * values :
			 *
			 * - `contents[<editable_id>][editor]`: The editor used to edit the contents
			 * - `contents[<editable_id>][contents]`: The content being edited.
			 *
			 */

			if (isset($editable['editor']))
			{
				if (!isset($app->editors[$editor_id]))
				{
					$elements["contents[$id]"] = new Alert
					(
						$app->translate('Éditeur inconnu : %editor', [ '%editor' => $editable['editor'] ]), [

							Form::LABEL => $title,
							Element::GROUP => $does_inherit ? 'contents.inherit' : 'contents',
							Alert::CONTEXT => Alert::CONTEXT_DANGER

						]
					);

					continue;
				}

				$editor = $app->editors[$editor_id];

				$elements["contents[$id]"] = $editor->from([

					Form::LABEL => $title,

					EditorElement::STYLESHEETS => $styles,
					EditorElement::CONFIG => $editor_config,

					Element::GROUP => $does_inherit ? 'contents.inherit' : 'contents',
					Element::DESCRIPTION => $editor_description,

					'id' => 'editor-' . $id,
					'value' => $editor->unserialize($value)

				]);

				#
				# we add the editor's id as a hidden field
				#

				$hiddens["editors[$id]"] = $editable['editor'];
			}
			else
			{
				$elements["contents[$id]"] = new MultiEditorElement($editor_id, [

					Form::LABEL => $title,

					MultiEditorElement::NOT_SWAPPABLE => isset($editable['editor']),
					MultiEditorElement::SELECTOR_NAME => "editors[$id]",
					MultiEditorElement::EDITOR_TAGS => [

						EditorElement::STYLESHEETS => $styles,
						EditorElement::CONFIG => $editor_config

					],

					Element::GROUP => $does_inherit ? 'contents.inherit' : 'contents',
					Element::DESCRIPTION => $editor_description,

					'id' => 'editor-' . $id,
					'value' => $editor_id ? $app->editors[$editor_id]->unserialize($value) : $value

				]);
			}
		}

		return [ $elements, $hiddens ];
	}

	/**
	 * Returns the template to use for a specified page.
	 *
	 * @param int $nid
	 * @param string $request_template
	 *
	 * @return array An array composed of the template name, the description and a boolean
	 * representing whether or not the template is inherited for the specified page.
	 */
	protected function resolve_template($nid, $request_template = null)
	{
		$inherited = false;
		$is_alone = !$this->model->select('nid')->filter_by_site_id($this->app->site_id)->rc;
		$template = null;

		if ($is_alone)
		{
			$template = 'home.html';
		}

		$app = $this->app;

		$description = $app->translate("The template defines a page model of which some elements are editable.");

		if (!$nid)
		{
			if ($is_alone)
			{
				$description .= " Parce que la page est seule elle utilise le gabarit <q>home.html</q>.";
			}
			else if (!$request_template)
			{
				$template = 'page.html';
			}
			else
			{
				$template = $request_template;
			}

			return [ $template, $description, $template == 'page.html' ];
		}

		/* @var $record Page */

		$record = $this->model[$nid];
		$definer = null;
		$template = $request_template !== null ? $request_template : $record->template;

//		\ICanBoogie\log('template: \1 (requested: \3), is_home: \2', [ $template, $record->is_home, $request_template ]);

		if ($template == 'page.html' && (!$record->parent || ($record->parent && $record->parent->is_home)))
		{
//			\ICanBoogie\log('page parent is home, hence the page.html template');

			$inherited = true;

			// TODO-20100507: à réviser, parce que la page peut ne pas avoir de parent.

			$description .= ' ' . "Parce qu'aucun gabarit n'est défini pour la page, elle utilise
			le gabarit <q>page.html</q>.";
		}
		else if ($template == 'home.html' && (!$record->parent && $record->weight == 0))
		{
			$inherited = true;

			//$template_description .= ' ' . "Cette page utilise le gabarit &laquo;&nbsp;home.html&nbsp;&raquo;.";
		}
		else if (!$request_template)
		{
			$definer = $record->parent;

			if (!$definer)
			{
				$template = 'page.html';
				$inherited = true;

				$description .= ' ' . "Parce qu'aucun gabarit n'est défini pour la page, elle utilise
				le gabarit <q>page.html</q>.";
			}
		}
		else
		{
			$definer = $record;
			$parent = $record->parent;

			while ($parent)
			{
				if ($parent->template == $request_template)
				{
					break;
				}

				$parent = $parent->parent;
			}

			if ($parent && $parent->template == $request_template)
			{
				$definer = $parent;
			}
		}

		if ($definer && $definer != $record)
		{
			$description .= ' ' . $app->translate
			(
				'This page uses the <q>:template</q> template, inherited from the parent page <q><a href="!url">!title</a></q>.', [

					'template' => $template,
					'url' => $this->app->url_for("admin:{$this->id}:edit", $definer),
					'title' => $definer->title

				]
			);

			$inherited = true;
		}

		return [ $template, $description, $inherited ];
	}

	static public function get_template_info($name)
	{
		$renderer = new PageRenderer;

		try
		{
			$path = $renderer->resolve_template_pathname($name);
		}
		catch (TemplateNotFound $e)
		{
			\ICanBoogie\log_error('Unknown template file %name', [ '%name' => $name ]);

			return [];
		}

		$html = file_get_contents($path); // This is assuming that the template engine is Patron
		$parser = new HTMLParser;

		return self::get_template_info_callback($html, $parser, $renderer);
	}

	static protected function get_template_info_callback($html, HTMLParser $parser, PageRenderer $renderer)
	{
		$styles = [];
		$contents = [];

		#
		# search css files
		#

		preg_match_all('#<link.*type="text/css".*>#', $html, $matches);

		foreach ($matches[0] as $match)
		{
			preg_match_all('#(\S+)="([^"]+)"#', $match, $attributes_matches, PREG_SET_ORDER);

			$attributes = [];

			foreach ($attributes_matches as $attribute_match)
			{
				list(, $attribute, $value) = $attribute_match;

				$attributes[$attribute] = $value;
			}

			if (isset($attributes['media']) && $attributes['media'] != 'screen')
			{
				continue;
			}

			$styles[] = $attributes['href'];
		}

		#
		#
		#

		$tree = $parser->parse($html, Patron::PREFIX);

		#
		# contents
		#

		$contents_collection = HTMLParser::collectMarkup($tree, 'page:content');

		foreach ($contents_collection as $node)
		{
			if (isset($node['children']))
			{
				foreach ($node['children'] as $child)
				{
					if (!is_array($child))
					{
						continue;
					}

					if ($child['name'] != 'with-param')
					{
						continue;
					}

					$param = $child['args']['name'];

					// TODO: what about arrays ? we should create a tree to string function

					$value = '';

					foreach ($child['children'] as $cv)
					{
						$value .= $cv;
					}

					$node['args'][$param] =	$value;
				}
			}

			$contents[] = $node['args'] + [

				'editor' => null,
				'config' => null,
				'description' => null

			];
		}

		#
		# recurse on templates
		#

		$call_template_collection = HTMLParser::collectMarkup($tree, 'call-template');
		$template_resolver = $renderer->template_resolver;
		$template_extensions = $renderer->template_extensions;

		foreach ($call_template_collection as $node)
		{
			$template_name = $node['args']['name'];
			$tried = [];
			$path = $template_resolver->resolve('pages/_' . $template_name, $template_extensions, $tried);

			if (!$path)
			{
				$e = new TemplateNotFound(\ICanBoogie\format("Partial template not found: %name", [ 'name' => $template_name ]), $tried);

				\ICanBoogie\log_error($e->getMessage());

				continue;
			}

			$template = file_get_contents($path);

			list($partial_contents, $partial_styles) = self::get_template_info_callback($template, $parser, $renderer);

			$contents = array_merge($contents, $partial_contents);

			if ($partial_styles)
			{
				$styles = array_merge($styles, $partial_styles);
			}
		}

		#
		# and decorators
		#

		foreach (HTMLParser::collectMarkup($tree, 'decorate') as $node)
		{
			$partial_name = $node['args']['with'];
			$tried = [];
			$path = $template_resolver->resolve('pages/@' . $partial_name, $template_extensions, $tried);

			if (!$path)
			{
				$e = new TemplateNotFound(\ICanBoogie\format("Partial template not found: %name", [ 'name' => $partial_name ]), $tried);

				\ICanBoogie\log_error($e->getMessage());

				continue;
			}

			$template = file_get_contents($path);

			list($partial_contents, $partial_styles) = self::get_template_info_callback($template, $parser, $renderer);

			$contents = array_merge($contents, $partial_contents);

			if ($partial_styles)
			{
				$styles = array_merge($styles, $partial_styles);
			}
		}

		return [ $contents, $styles ];
	}
}
