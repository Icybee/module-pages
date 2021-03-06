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

use Brickrouge\Form;
use ICanBoogie\ErrorCollection;
use ICanBoogie\Operation;

use Icybee\Binding\Core\PrototypedBindings;
use Icybee\Modules\Files\Module;

/**
 * @property Module $module
 */
class TemplateEditorsOperation extends Operation
{
	use PrototypedBindings;

	protected function get_controls()
	{
		return [

			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE

		] + parent::get_controls();
	}

	/**
	 * @inheritdoc
	 */
	protected function validate(ErrorCollection $errors)
	{
		return $errors;
	}

	/**
	 * Returns a sectioned form with the editors to use to edit the contents of a template.
	 *
	 * The function alters the operation object by adding the `template` property, which holds an
	 * array with the following keys:
	 *
	 * - `name`: The name of the template.
	 * - `description`: The description for the template.
	 * - `inherited`: Whether or not the template is inherited.
	 *
	 * The function also alters the operation object by adding the `assets` property, which holds
	 * an array with the following keys:
	 *
	 * - `css`: An array of CSS files URL.
	 * - `js`: An array of Javascript files URL.
	 *
	 * @return string The HTML code for the form.
	 */
	protected function process()
	{
		$request = $this->request;
		$template = $request['template'];
		$page_id = $request['page_id'];

		list($contents_tags, $template_info) = $this->module->get_contents_section($page_id, $template);

		$this->response['template'] = $template_info;

		$form = (string) new Form([ Form::RENDERER => Form\GroupRenderer::class ] + $contents_tags);

		$this->response['assets'] = $this->app->document->assets;

		return $form;
	}
}
