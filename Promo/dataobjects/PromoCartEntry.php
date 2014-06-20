<?php

require_once 'Store/dataobjects/StoreCartEntry.php';

/**
 * PromoCartEntry data object
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoCartEntry extends StoreCartEntry
{
	// {{{ public properties

	public $promotion_discount;

	// }}}
	// {{{ public function createOrderItem()

	/**
	 * Creates a new order item dataobject that corresponds to this cart entry
	 *
	 * @return StoreOrderItem a new StoreOrderItem object that corresponds to
	 *                         this cart entry.
	 */
	public function createOrderItem()
	{
		$order_item = parent::createOrderItem();
		$order_item->promotion_discount = $this->promotion_discount;

		return $order_item;
	}

	// }}}
}

?>
