<?php

require_once 'Store/dataobjects/StoreOrderItem.php';

/**
 * Promo specific order item
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoOrderItem extends StoreOrderItem
{
	// {{{ public properties

	public $promotion_discount;

	// }}}
}

?>
