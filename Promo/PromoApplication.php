<?php

/**
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoApplication extends StoreApplication
{


	protected function initModules()
	{
		$this->session->registerObject(
			'promotion',
			PromoPromotion::class
		);

		parent::initModules();
	}


}

?>
