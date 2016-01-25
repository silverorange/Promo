<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * A recordset wrapper class for PromoPromotion objects
 *
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       PromoPromotion
 */
class PromoPromotionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PromoPromotion');
		$this->index_field = 'id';
	}

	// }}}
}

?>
