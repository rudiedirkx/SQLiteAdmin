<?php

return array(
	'tables' => array(
		'aliases' => array(
			'id' => array('pk' => true),
			'alias',
			'path',
			'description',
		),
		'favorites' => array(
			'id' => array('pk' => true),
			'alias_id' => array('unsigned' => true),
			'name',
			'tbl',
			'query',
			'created_on' => array('unsigned' => true),
		),
	),
	'data' => array(
		'aliases' => array(
			array(
				'alias' => 'mgr_cfg',
				'path' => 'config/config.db',
			),
		),
	),
);


