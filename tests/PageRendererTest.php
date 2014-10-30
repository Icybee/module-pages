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

class PageRendererTest extends \PHPUnit_Framework_TestCase
{
	public function test_render()
	{
		$expected = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My page</title>
</head>
<body>
My body <em>with some <strong>HTML</strong></em>
</body>
</html>
EOT;

		$page = Page::from([

			'title' => "My page",
			'body' => "My body <em>with some <strong>HTML</strong></em>"

		]);

		$this->assertEquals('page.html', $page->template);

		$renderer = new PageRenderer;
		$html = $renderer($page);

		$this->assertEquals($expected, $html);
		$this->assertEquals($expected, $page->render());
		$this->assertEquals($expected, (string) $page);
	}
}
