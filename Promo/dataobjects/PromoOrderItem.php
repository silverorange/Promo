<?php

/**
 * Promo specific order item.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property ?float $promotion_discount
 */
class PromoOrderItem extends StoreOrderItem
{
    /**
     * @var float
     */
    public $promotion_discount;
}
