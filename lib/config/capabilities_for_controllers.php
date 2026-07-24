<?php
/**
 * Controller capability map.
 *
 * `default`    - capabilities required by every action of the controller.
 * `per_action` - overrides for individual actions.
 *
 * A controller that is not listed here falls back to `settings__edit`, the most
 * restrictive capability, so forgetting to register a new controller locks it
 * down rather than opening it up.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'MdDashboardController'  => array(
		'default' => array( 'article__view' ),
	),

	'MdArticlesController'   => array(
		'default'    => array( 'article__edit' ),
		'per_action' => array(
			'index'      => array( 'article__view' ),
			'quick_edit' => array( 'article__view' ),
			'create'     => array( 'article__create' ),
			'update'     => array( 'article__edit' ),
			'destroy'    => array( 'article__delete' ),
		),
	),

	'MdCategoriesController' => array(
		'default'    => array( 'category__edit' ),
		'per_action' => array(
			'index'      => array( 'category__view' ),
			'quick_edit' => array( 'category__view' ),
			'create'     => array( 'category__create' ),
			'update'     => array( 'category__edit' ),
			'destroy'    => array( 'category__delete' ),
		),
	),

	'MdSettingsController'   => array(
		'default' => array( 'settings__edit' ),
	),
);
