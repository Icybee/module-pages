<?php

/* @var $app \ICanBoogie\Core */
/* @var $this \Icybee\Modules\Pages\Blueprint */

/**
 * @param $branches
 * @param bool $depth
 * @param bool $min_child
 * @param int $level
 *
 * @return string|void
 */
$builder = function($branches, $depth=false, $min_child=false, $level=1) use(&$builder)
{
	$rc = '';

	foreach ($branches as $branch)
	{
		if ($level == 1 && ($min_child !== false && (count($branch->children) < $min_child)))
		{
			continue;
		}

		$class = '';

		if ($branch->children)
		{
			$class .= 'has-children';
		}

		$record = $branch->record;

		if (!empty($record->is_active))
		{
			if ($class)
			{
				$class .= ' ';
			}

			$class .= 'active';
		}

		$class .= ' nid-' . $record->nid;

		$rc .=  $class ? '<li class="' . trim($class) . '">' : '<li>';
		$rc .= '<a href="' . $record->url . '">' . \ICanBoogie\escape($record->label) . '</a>';

		if (($depth === false || $level < $depth) && $branch->children)
		{
			$rc .= $builder($branch->children, $depth, $min_child, $level + 1);
		}

		$rc .= '</li>';
	}

	if (!$rc)
	{
		return;
	}

	return '<ol class="lv' . $level . '">' . $rc . '</ol>';
};

$subset = $this->subset(function($branch){

	return ($branch->pattern || !$branch->is_online);

});

$subset->populate();

echo $builder($subset->tree);
