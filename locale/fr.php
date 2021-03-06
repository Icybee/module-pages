<?php

return [

	'button.Reset' => "Rétablir",

	'pages' => [

		'count' => [

			'none' => 'Aucune page',
			'one' => 'Une page',
			'other' => ':count pages'

		],

		'name' => [

			'one' => 'Page',
			'other' => 'Pages'

		],

		'search' => [

			'found' => [

				'none' => 'Aucun résultat trouvé dans les pages.',
				'one' => 'Un résultat trouvé dans les pages.',
				'other' => ':count résultats trouvés dans les pages.'

			],

			'more' => [

				'one' => 'Voir le résultat trouvé pour %search dans les pages',
				'other' => 'Voir les :count résultats trouvés pour %search dans les pages'

			]

		]

	],

	'content.title' => [

		'body' => 'Corps de la page'

	],

	'description' => [

		'is_navigation_excluded' => "Les pages exclues de la navigation principale n'apparaissent pas dans les menus.
		Elles peuvent tout de même apparaitre sur le plan du site.",

		'label' => "L'étiquette est une version plus concise du titre. Elle est utilisée de
		préférence au titre pour créer les liens des menus et du fil d'Ariane.",

		'location' => 'Redirection depuis cette page vers une autre page.',

		'parent_id' => "Organisez les pages hiérarchiquement pour former une arborescence.",

		'pattern' => "Le motif permet de distribuer les paramètres d'une URL pour créer une URL
		sémantique.",

		'contents.inherit' => "Les contenus suivants peuvent être hérités. Si la page ne définit
		pas un contenu, alors celui d'une page parente est utilisé."

	],

	'label' => [

		'is_navigation_excluded' => 'Exclure la page de la navigation principale',
		'label' => 'Étiquette de la page',
		'location' => 'Redirection',
		'parent_id' => 'Page parente',
		'pattern' => 'Motif',
		'template' => 'Gabarit'

	],

	'group.legend' => [

		'Template' => 'Gabarit'

	],

	"The template defines a page model of which some elements are editable."
	=> "Le gabarit définit un modèle de page dont certains éléments sont modifiables.",

	"The following elements are editable:"
	=> "Les éléments suivants sont éditables&nbsp;:",

	"The <q>:template</q> template does not define any editable element."
	=> "Le gabarit <q>:template</q> ne définit aucun élément éditable.",

	'No parent page define this content.'
	=> "Aucune page parente ne définit ce contenu.",

	'This content is currently inherited from the <q><a href="!url">!title</a></q> parent page – <a href="#edit">Edit the content</a>'
	=> 'Ce contenu est actuellement hérité depuis la page parente <q><a href="!url">!title</a></q> – <a href="#edit">Éditer le contenu</a>',

	'This page uses the <q>:template</q> template, inherited from the parent page <q><a href="!url">!title</a></q>.'
	=> 'Cette page utilise le gabarit <q>:template</q>, hérité de la page parente <q><a href="!url">!title</a></q>.',

	"The page tree has been changed"
	=> "L'arborescence a été modifiée",

	"All records" => "Tous les enregistrements",

	'Record detail' => "Détail d'un enregistrement",
	'Records list' => "Liste des enregistrements",
	'Records home' => "Accueil des enregistrements"

];
