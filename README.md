# Installation
`composer require whatson-web/cms-bundle dev-master`

`app/console wh:install:bundle cms`

## Ajouter les routes
```yaml
bk_cms:
    resource: "@CmsBundle/Controller/Backend/"
    type:     annotation
```

## Base configuration
```yaml
wh_cms:
    templates:
        home:
            name: 'Accueil'
            frontView: 'CmsBundle:FrontEnd/Page:home.html.twig'
        page:
            name: 'Page normale'
```

## Base configuration SEO
```yaml
wh_seo:
    entities:
        CmsBundle\Entity\Page:
            urlFields:
                - {type: 'tree', entity: 'CmsBundle\Entity\Page', field: 'parent'}
                - {type: 'field', field: 'slug', suffix: '/'}
            defaultMetasFields:
                title: 'name'
                description: 'resume'
```

## Ajouter l'onglet dans le menu admin
Ajouter le code suivant dans le fichier : `src/BackendBundle/Menu/Menu.php`

	$menu->addChild(
		'pages',
		array(
			'label'  => $this->getLabel('sitemap', 'Pages'),
			'route'  => 'bk_cms_page_index',
			'extras' => array(
				'safe_label' => true,
			),
		)
	);
