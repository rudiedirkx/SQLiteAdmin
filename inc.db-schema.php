<?php

return array(
	'tables' => array(
		'aliases' => array(
			'id' => array('pk' => true),
			'alias',
			'path',
			'description',
			'public' => array('unsigned' => true),
		),
		'users' => array(
			'id' => array('pk' => true),
			'username',
			'password',
			'user_type' => array('unsigned' => true),
		),
		'user_alias_access' => array(
			'user_id' => array('unsigned' => true),
			'alias_id' => array('unsigned' => true),
			'allowed_queries',
		),
		'favorites' => array(
			'id' => array('pk' => true),
			'user_id' => array('unsigned' => true),
			'alias_id' => array('unsigned' => true),
			'name',
			'tbl',
			'query',
			'created_on' => array('unsigned' => true),
			'is_public' => array('unsigned' => true),
		),
	),
	'data' => array(
		'aliases' => array(
			array(
				'alias' => 'mgr_cfg',
				'path' => 'config/config.db',
				'public' => 0,
			),
		),
		'users' => array(
			array(
				'username' => 'rudie',
				'password' => 'd678e10e7c944dc4ebe23955cce435272f134d5e',
				'user_type' => 0,
			),
		),
	),
);


