<?php

/**
 * PromoCartEntry data object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property ?float $promotion_discount
 */
class PromoCartEntry extends StoreCartEntry
{
    /**
     * @var float
     */
    public $promotion_discount;

    /**
     * Creates a new order item dataobject that corresponds to this cart entry.
     *
     * @return StoreOrderItem a new StoreOrderItem object that corresponds to
     *                        this cart entry
     */
    public function createOrderItem()
    {
        $order_item = parent::createOrderItem();
        $order_item->promotion_discount = $this->promotion_discount;

        return $order_item;
    }
}
