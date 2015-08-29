<?php

namespace Brickrouge;

/**
 * @var $this \Icybee\Modules\Pages\Page
 * @var $page \Icybee\Modules\Pages\Page
 * @var $document \Icybee\Document
 * @var $tried_templates array
 */

$current_template = array_pop($tried_templates);

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?= escape($this->title) ?></title>

	<?= $document->css ?>
</head>
<body>

<div class="container">
	<p>THIS IS A SAMPLE TEMPLATE</p>

	<p>This view was rendered using the following template:</p>

	<p><code><?= $current_template ?></code></p>

	<p>You may override this by creating one of the following template:</p>

	<ul>
		<?php foreach($tried_templates as $template): ?>
		<li><code><?= $template ?></code></li>
		<?php endforeach; ?>
	</ul>
</div>

<?= $document->js ?>

</body>
