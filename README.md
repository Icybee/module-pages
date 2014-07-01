# Pages [![Build Status](https://travis-ci.org/Icybee/modules-pages.png?branch=2.0)](https://travis-ci.org/Icybee/modules-pages)

The Pages module (`pages`) introduces the "Page" content type to the CMS
[Icybee](http://icybee.org). Pages are used to created the website tree, display contents and
views. The module provides a request dispatcher to serve the pages it manages.





## Blueprint

A blueprint is a simplified data structure representing the relashionship between pages. It
provides child/parent relations, parent/children relations, an index, and a tree representation.
The blueprint can be created from a [Query][] or can be obtained from the `pages` model:

```php
<?php

#
# Obtain a cached blueprint with only the properties required to build the blueprint: `nid`,
# `parentid`, `is_online`, `is_navigation_excluded`, `pattern`.
#

$blueprint = $core->models['pages']->blueprint($site_id = 1);

$blueprint->relations; // child/parent relations
$blueprint->children;  // parent/children relations
$blueprint->index;     // index
$blueprint->tree;      // pages nested in a tree
$blueprint->model;     // the model associated with the blueprint
```





### Obtaining a subset of a blueprint

A subset can be created from a blueprint, this is interesting when you whish to work
with a particuliar branch, or only the nodes that have a maximum depth of 2, or maybe only the
online nodes.

The following example demonstrates how a subset of a blueprint with only the branch of
a particuliar branch can be obtained:

```php
<?php

$subset = $core->models['pages']->blueprint($site_id = 1)->subset(123);
```

The following example demonstrates how a subset of a blueprint with nodes that have
a maximum depth of 2 can be obtained:

```php
<?php

$subset = $core->models['pages']->blueprint($site_id = 1)->subset(null, 2);
```

The following example demonstrates how subset of a blueprint with only the online
nodes can be obtained:

```php
<?php

use Icybee\Modules\Pages\BlueprintNode;

$subset = $core->models['pages']
->blueprint($site_id = 1)
->subset(null, null, function(BlueprintNode $node) {

	return !$node->is_online;

});
/* or
->subset(function(BlueprintNode $node) {

	return !$node->is_online;

}); */
```





### Populating a blueprint

Once you have obtained a blueprint you might want to populate it with the actual records. The
`populate()` method populates the blueprint by loading the records associated, and updates the
nodes of the blueprint with these records. Don't worry about performance, the records are obtained
with a single query to the database.

```php
<?php

$blueprint->populate();

foreach ($blueprint->index as $node)
{
	var_dump($node->record);
}
```





### Obtening and ordered array of nodes or records

Through the `ordered_nodes` and `ordered_records` read-only properties you can obtain an array of
nodes or records. They are ordered according to their weight and relation.

```php
<?php

$blueprint->ordered_nodes;   // an array of BluePrintNodes instances
$blueprint->ordered_records; // an array of Page instances
```





### Traversing a blueprint

The `index` of the blueprint holds all of its nodes in a flat array. The order is non important.

The following example demonstrates how to traverse a blueprint using its index:

```php
<?php

foreach ($blueprint->index as $node);
# or
foreach ($blueprint as $node);
```





## Prototype methods





### `Icybee\Modules\Sites\Site::lazy_get_home`

The `home` getter is added to instances of `Icybee\Modules\Sites\Site`. It returns the home
page of the instance:

```php
<?php

echo "Home page URL: " . $core->site->home->url;
```





### `ICanBoogie\Core::get_page`

The `page` getter is added to instances of `ICanBoogie\Core`. It returns the page currently being
displayed. The getter is a shortcut to `$core->request->context->page`.





----------





## Requirement

The package requires PHP 5.4 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
	"minimum-stability": "dev",
	"require":
	{
		"icybee/module-pages": "2.x"
	}
}
```





### Cloning the repository

The package is [available on GitHub](https://github.com/Icybee/module-pages), its repository can be
cloned with the following command line:

	$ git clone git://github.com/Icybee/module-pages.git pages





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all the dependencies required to run the suite. The package
directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://travis-ci.org/Icybee/modules-pages.png?branch=2.0)](https://travis-ci.org/Icybee/modules-pages)





## Documentation

The documentation for the package and its dependencies can be generated with the `make doc`
command. The documentation is generated in the `docs` directory using [ApiGen](http://apigen.org/).
The package directory can later by cleaned with the `make clean` command.





## License

This package is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[Query]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.Query.html