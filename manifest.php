<?php
$manifest = array(
    'name' => 'multiform',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Несколько форм в форме',
    'is_uninstallable' => true,
    'published_date' => '2017-01-13',
    'type' => 'module',
    'version' => '1.2.1',
);
$installdefs = array(
    'id' => 'multiform',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
