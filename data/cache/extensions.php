<?php
$EXT_CONF['example'] = array(
	'title' => 'Example Extension',
	'description' => 'This sample extension demonstrates the use of various hooks',
	'disable' => true,
	'version' => '1.0.1',
	'releasedate' => '2018-03-21',
	'author' => array('name'=>'Uwe Steinmann', 'email'=>'uwe@steinmann.cx', 'company'=>'MMK GmbH'),
	'config' => array(
		'input_field' => array(
			'title'=>'Example input field',
			'type'=>'input',
			'size'=>20,
		),
		'checkbox' => array(
			'title'=>'Example check box',
			'type'=>'checkbox',
		),
		'list' => array(
			'title'=>'Example select menu from options',
			'type'=>'select',
			'options' => array('Option 1', 'Option 2', 'Option 3'),
			'multiple' => true,
			'size' => 2,
		),
		'categories' => array(
			'title'=>'Example select menu from categories',
			'type'=>'select',
			'internal'=>'categories',
			'multiple' => true,
		),
		'users' => array(
			'title'=>'Example select menu from users',
			'type'=>'select',
			'internal'=>'users',
			'multiple' => true,
		),
		'groups' => array(
			'title'=>'Example select menu from groups',
			'type'=>'select',
			'internal'=>'groups',
			'multiple' => true,
		),
		'attributedefinitions' => array(
			'title'=>'Example select menu from attribute definitions',
			'type'=>'select',
			'internal'=>'attributedefinitions',
			'multiple' => true,
		),
	),
	'constraints' => array(
		'depends' => array('php' => '5.6.40-', 'seeddms' => '5.1.0-'),
	),
	'icon' => 'icon.svg',
	'changelog' => 'changelog.md',
	'class' => array(
		'file' => 'class.example.php',
		'name' => 'SeedDMS_ExtExample'
	),
	'language' => array(
		'file' => 'lang.php',
	),
);
?>
