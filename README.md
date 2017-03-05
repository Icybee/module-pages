# Pages

[![Packagist](https://img.shields.io/packagist/v/icybee/module-pages.svg)](https://packagist.org/packages/icybee/module-pages)
[![Build Status](https://img.shields.io/travis/Icybee/module-pages.svg)](http://travis-ci.org/Icybee/module-pages)
[![HHVM](https://img.shields.io/hhvm/Icybee/module-pages.svg)](http://hhvm.h4cc.de/package/Icybee/module-pages)
[![Code Quality](https://img.shields.io/scrutinizer/g/Icybee/module-pages.svg)](https://scrutinizer-ci.com/g/Icybee/module-pages)
[![Code Coverage](https://img.shields.io/coveralls/Icybee/module-pages.svg)](https://coveralls.io/r/Icybee/module-pages)
[![Downloads](https://img.shields.io/packagist/dt/icybee/module-pages.svg)](https://packagist.org/packages/icybee/module-pages/stats)

The Pages module (`pages`) introduces the "Page" content type to the CMS
[Icybee](http://icybee.org). Pages are used to created the website tree, display contents and
views. The module provides a request dispatcher to serve the pages it manages.





## Blueprint

A blueprint is a simplified data structure representing the relationship between pages. It
provides child/parent relations, parent/children relations, an index, and a tree representation.
The blueprint can be created from a [Query][] or can be obtained from the `pages` model.

The following properties are available:

- `relations`: Child/parent relations, key to key.
- `children`: Parent/children relations, key to key.
- `index`: The blueprint nodes, indexed by key.
- `tree`: The blueprint nodes, nested in a tree.
- `model`: The model associated with the blueprint.





### Obtaining a blueprint from a query

The following example demonstrates how a blueprint can be obtained from a [Query][] instance. Only
the `nid` and `parent_id` properties are required to build the bulueprint, but you might want
more than that to be useful. In the example, the blueprint is created with the additional
properties `slug` and `pattern`:

```php
<?php

$query = $app->models['pages']
->select('nid, parent_id, slug, pattern')
->filter_by_site_id($site_id = 1)
->ordered;

$blueprint = Blueprint::from($query);
```





### Obtaining a blueprint from the model

A blueprint can be obtained from the `pages` model. This blueprint is often used to compute
navigation or resolve routes. It is created with the additional properties `is_online`,
`is_navigation_excluded`, and `pattern`.

**Note**: This blueprint is cached, that is two calls to the `blueprint()` method with the same
argument yield the same instance. If you require to modify the blueprint itself you are advised to
use a clone.

```php
<?php

$blueprint = $app->models['pages']->blueprint($site_id = 1);
```





### Obtaining a subset of a blueprint

A subset can be created from a blueprint, this is interesting when you wish to work
with a particular branch, or only the nodes that have a maximum depth of 2, or maybe only the
online nodes.

The following example demonstrates how a subset of a blueprint with only the branch of
a particular branch can be obtained:

```php
<?php

$subset = $app->models['pages']->blueprint($site_id = 1)->subset(123);
```

The following example demonstrates how a subset of a blueprint with nodes that have
a maximum depth of 2 can be obtained:

```php
<?php

$subset = $app->models['pages']->blueprint($site_id = 1)->subset(null, 2);
```

The following example demonstrates how a subset of a blueprint with only the online
nodes can be obtained using a closure:

```php
<?php

use Icybee\Modules\Pages\BlueprintNode;

$subset = $app->models['pages']
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

Once you have obtained a blueprint you might want to populate it with actual records. The
`populate()` method populates a blueprint by loading the associated records, and updates the
blueprint nodes with them. Don't worry about performance, the records are obtained with a single
query through the `find()` method of the model, and benefit from its caching mechanisms.

```php
<?php

$blueprint->populate();

foreach ($blueprint as $node)
{
	var_dump($node->record);
}

# or

foreach ($blueprint->populate() as $record)
{
	var_dump($record);
}
```





### Obtening and ordered array of nodes or records

Through the `ordered_nodes` and `ordered_records` read-only properties you can obtain an array of
nodes or records. They are ordered according to their weight and relation.

```php
<?php

$blueprint->ordered_nodes;	// an array of BlueprintNodes instances
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





## The navigation element

The [NavigationElement][] class makes it very easy to render a navigation element from a blueprint.

```php
<?php

use Icybee\Modules\Pages\NavigationElement;

echo new NavigationElement($blueprint);
```

Will render something like this (prettyfied for lisibility):

```html
<ol class="nav lv1">
	<li class="page page-id-1 has-children trail">
		<a href="/example1">Example 1</a>
		<ol class="dropdown-menu lv2">
			<li class="page page-id-10 active"><a href="/example1/example-a.html">Example A</a></li>
			<li class="page page-id-11"><a href="/example1/example-b.html">Example B</a></li>
		</ol>
	</li>
	<li class="page page-id-4">
		<a href="/contact.html">Contact</a>
	</li>
</ol>
```

### Before the navigation element is populated with children

The event `Icybee\Modules\Pages\NavigationElement::populate:before`
of class [BeforePopulateEvent][] is fired before the navigation element is populated with children.

Third parties may use this event to alter the blueprint. For instance, using a subset instead of
the complete blueprint.

The following code demonstrates how the node with id "5" is discarded from the navigation:

```php
<?php

use Icybee\Modules\Pages\BlueprintNode;
use Icybee\Modules\Pages\NavigationElement;

$app->events->attach(function(NavigationElement\BeforePopulateEvent $event, NavigationElement $target) {

	$event->blueprint = $event->blueprint->subset(function(BlueprintNode $node) {

		return $node->nid == 5;

	});

});
```





### After the navigation element was populated with children

The event `Icybee\Modules\Pages\NavigationElement::populate`
of class [PopulateEvent][] is fired after the navigation element was populated with children.

Third parties may use this event to alter the renderable elements of the navigation. For
instance, one can replace links, classes or titles.

The following example demonstrates how to alter the `href` and `target` attributes of
navigation links:

```php
<?php

use Icybee\Modules\Pages\NavigationElement;

$app->events->attach(function(NavigationElement\PopulateEvent $event, NavigationElement $target) {

	foreach ($event->blueprint as $node)
	{
		$link = $node->renderables['link'];

		$link['href'] = '#';
		$link['target'] = '_blank';
	}

});
```





## Rendering pages

[Page][] instances are rendered using a [PageRenderer][] instance. This is usually handled by the
[PageController], but sometimes you might want to do that yourself, without being required to
dispatch a request, for example when rendering a [Page][] instance that you have created yourself.

Events are fired before and after the rendering, allowing third parties to alter the rendering.

```php
<?php

use Icybee\Modules\Pages\Page;
use Icybee\Modules\Pages\PageRenderer;

$page = Page::form([

	'title' => "My page",
	'body' => "My body"

]);

$renderer = new PageRenderer;
$html = $renderer($page);
```

The module also provides a default renderer that is used when rendering a [Page][] instance to a
HTML string with either the `render()` prototype method, or `__toString()`.

```php
<?php

$html = $page->render();
$html = (string) $page;
```

You can override the `render()` method to use your own renderer:

```php
<?php

use ICanBoogie\Prototype;

Prototype::from('Icybee\Modules\Pages\Page')['render'] = function(Page $page)
{
	// …

	return $html;
};
```





### Before the rendering

The event `Icybee\Modules\Pages\PageRenderer::render:before` event of class [BeforeRenderEvent][]
is fired before the page is rendered. Third parties may use this event to alter the rendering
context, or the assets of the document.

```php
<?php

use Icybee\Modules\Pages\PageRenderer;

$app->events->attach(function(PageRenderer\BeforeRenderEvent $event, PageRenderer $target) {

	$event->context['my_variable'] = "My value";

	$event->document->css->add('/public/page.css');
	$event->document->js->add('/public/page.js');

});
```





### After the rendering

The event `Icybee\Modules\Pages\PageRenderer::render` event of class [RenderEvent][] is fired
after the page was rendered. Third parties may use this event to alter the HTML string produced.

```php
<?php

use Icybee\Modules\Pages\PageRenderer;

$app->events->attach(function(PageRenderer\RenderEvent $event, PageRenderer $target) {

	$event->html .= "<!-- My awesome comment -->";

});
```

The `inject()` method is used to insert a HTML fragment relative to an element in the produced
HTML. The following example demonstrates how the content of a `$fragment` variable can be injected
at the bottom of the `BODY` element.

```php
<?php

	// …

	$event->inject($fragment, 'body');

	// …
```

The `replace()` method is used to replace a placeholder by a HTML fragment.

```php
<?php

	// …

	$event->replace($placeholder, $fragment);

	// …
```






## Prototype methods





### `Icybee\Modules\Sites\Site::lazy_get_home`

The `home` getter is added to instances of `Icybee\Modules\Sites\Site`. It returns the home
page of the instance:

```php
<?php

echo "Home page URL: " . $app->site->home->url;
```





### `ICanBoogie\Application::get_page`

The `page` getter is added to instances of `ICanBoogie\Application`. It returns the page currently
being displayed. The getter is a shortcut to `$app->request->context->page`.





## Patron markups

The following [Patron][] markups are defined by the module:

- `p:breadcrumb`
- `p:navigation`
- `p:navigation:leaf`
- `p:page:content`
- `p:page:languages`
- `p:page:region`
- `p:page:title`





### The `p:navigation` markup

Navigation elements for the current page are rendered with the `p:navigation` markup.

```html
<p:navigation
	css-class-names = string
	depth = int
	from-level = int
	min-children = int
	parent = int|string|Page>
	<!-- Content: p:with-param*, template? -->
</p:navigation>
```

The CSS class names to use by the navigation branch can be specified with the `css-class-names`
parameter. The default is "'-constructor -slug -template'", which removes the constructor, slug,
and template names. The maximum depth of the navigation is specified by the `depth` parameter.
The starting level of the navigation is specified by the `from-level` parameter. Using the
`min-children` parameter, navigation branches can be discarded if they don't include enough
direct children. Finally, the `parent` parameter can be used to specify the parent of the
navigation, which can be specified as a [Page][] instance, an identifier, or a path.

```html
<p:navigation />
<p:navigation css-class-names="id slug" />
<p:navigation parent="/blog" depth="1" />
```

The template is published with a [NavigationElement][] instance as _thisArg_.

```html
<p:navigation>
	#{@blueprint.dump()=}

	<ul class="nav">
	<p:foreach in="@blueprint.tree">
		<li class="#{css_class}"><a href="#{@url}">#{@label}</a></li>
	</p>
	</ul>
</p:navigation>
```






### The `p:navigation:leaf` markup

Render a navigation leaf from the current page.

```html
<p:navigation:leaf
	css-class-name = string
	depth = int>
	<!-- Content: p:with-param*, template? -->
</p:navigation:leaf>
```

`css-class-name` specifies the modifiers to use to generate the CSS classes of the header
and the content nodes. It defaults to "active trail id". `depth` is the maximum depth of the
branch. It defaults to 2.

The template is published with a [NavigationBranchElement][] instance as _thisArg_.

```html
<p:navigation:leaf />

<!-- or -->

<p:navigation:leaf>
	<div class="nav-branch">
		#{@rendered_header=}
		#{@rendered_content=}
	</div>
</p>
```






----------





## Requirement

The package requires PHP 5.5 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/):

```
$ composer require icybee/module-pages
```





### Cloning the repository

The package is [available on GitHub](https://github.com/Icybee/module-pages), its repository can be
cloned with the following command line:

	$ git clone https://github.com/Icybee/module-pages.git pages





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all the dependencies required to run the suite. The package
directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://img.shields.io/travis/Icybee/module-pages.svg)](http://travis-ci.org/Icybee/module-pages)
[![Code Coverage](https://img.shields.io/coveralls/Icybee/module-pages.svg)](https://coveralls.io/r/Icybee/module-pages)





## Documentation

The documentation for the package and its dependencies can be generated with the `make doc`
command. The documentation is generated in the `docs` directory using [ApiGen](http://apigen.org/).
The package directory can later by cleaned with the `make clean` command.





## License

This package is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[BeforePopulateEvent]: http://icybee.org/docs/class-Icybee.Modules.Pages.NavigationElement.BeforePopulateEvent.html
[BeforeRenderEvent]: http://icybee.org/docs/class-Icybee.Modules.Pages.PageRenderer.BeforeRenderEvent.html
[NavigationBranchElement]: http://icybee.org/docs/class-Icybee.Modules.Pages.NavigationBranchElement.html
[NavigationElement]: http://icybee.org/docs/class-Icybee.Modules.Pages.NavigationElement.html
[Page]: http://icybee.org/docs/class-Icybee.Modules.Pages.Page.html
[PageController]: http://icybee.org/docs/class-Icybee.Modules.Pages.PageController.html
[PageRenderer]: http://icybee.org/docs/class-Icybee.Modules.Pages.PageRenderer.html
[Patron]: https://github.com/Icybee/Patron
[PopulateEvent]: http://icybee.org/docs/class-Icybee.Modules.Pages.NavigationElement.PopulateEvent.html
[Query]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.Query.html
[RenderEvent]: http://icybee.org/docs/class-Icybee.Modules.Pages.PageRenderer.RenderEvent.html
