<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	'source_dir' => 'assets/',
	'target_dir' => Kohana::$cache_dir.'/assets/',

	'concatable' => array(),

	'types' => array(
		'coffee' => array('.coffee'),
		'css'    => array('.css'),
		'js'     => array('.js'),
		'less'   => array('.less'),
		'twig'   => array('.html')
	),

	'target_types' => array(
		'css'  => array('css', 'less'),
		'js'   => array('js', 'coffee'),
		'twig' => array('twig')
	),

	'watch' => Kohana::$environment === Kohana::DEVELOPMENT,
);

