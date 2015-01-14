<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Promo/dataobjects/PromoPromotionCode.php';

/**
 * A recordset wrapper class for PromoPromotionCode objects
 *
 * @package   Promo
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       PromoPromotionCode
 */
class PromoPromotionCodeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PromoPromotionCode');
		$this->index_field = 'id';
	}

	// }}}
}

?>
