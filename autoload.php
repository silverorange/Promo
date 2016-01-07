<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/promo');

$package->addRule(
	new Rule(
		'dataobjects',
		'Promo',
		array(
			'CartEntry',
			'OrderItem',
			'Order',
			'PromotionCode',
			'Promotion',
			'Wrapper'
		)
	)
);
$package->addRule(new Rule('', 'Promo'));

Autoloader::addPackage($package);

?>
