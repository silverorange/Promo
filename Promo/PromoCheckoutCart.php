<?php

require_once 'Swat/SwatNumber.php';
require_once 'Store/StoreCheckoutCart.php';

/**
 * Promo specific checkout cart
 *
 * @package   Promo
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @todo      The pro-rating of promotion and quantity discounts hasn't been
 *            tested with sale discounts, and probably is broken if we start
 *            using them. As well, there is an edge case that we don't handle.
 *            For example, item 1 is $1, item 2 is $1000, total discount is $3.
 *            In that case, depending on rounding, totalling the item discounts
 *            could be off by a small margin. Probably not worth fixing.
 */
abstract class PromoCheckoutCart extends StoreCheckoutCart
{
	// {{{ protected properties

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	// }}}
	// {{{ public function setPromotion()

	public function setPromotion(PromoPromotion $promotion)
	{
		$this->promotion = $promotion;
	}

	// }}}

	// totalling
	// {{{ public function getTotal()

	/**
	 * Gets the total cost for an order of the contents of this cart
	 *
	 * @param StoreAddress $billing_address the billing address of the order.
	 * @param StoreAddress $shipping_address the shipping address of the order.
	 * @param StoreShippingType $shipping_type the shipping type of the order.
	 * @param StoreOrderPaymentMethodWrapper $payment_methods the payment
	 *                                                         methods of the
	 *                                                         order.
	 *
	 * @return double the cost of this cart's contents.
	 */
	public function getTotal(StoreAddress $billing_address = null,
		StoreAddress $shipping_address = null,
		StoreShippingType $shipping_type = null,
		StoreOrderPaymentMethodWrapper $payment_methods = null)
	{
		if ($this->cachedValueExists('store-total')) {
			$total = $this->getCachedValue('store-total');
		} else {
			$total = 0;
			$total += $this->getItemTotal();
			$total -= $this->getPromotionDiscount();

			// discounts can't put the total below zero
			if ($total < 0) {
				$total = 0;
			}

			$total += $this->getShippingTotal($billing_address,
				$shipping_address, $shipping_type);

			$total += $this->getTaxTotal($billing_address,
				$shipping_address, $shipping_type, $payment_methods);

			$this->setCachedValue('store-total', $total);
		}

		return $total;
	}

	// }}}
	// {{{ public function getPromotionDiscount()

	/**
	 * Gets the promotion discount total of the contents of this cart
	 *
	 * Note: A percentage based promotion will apply against the full price of
	 * the items, and not factor in any quantity discounts.
	 *
	 * @param array $entries Cart entries to check to use to calculate promotion
	 *                       discount.
	 *
	 * @return double the promotion discount total of this cart's contents.
	 */
	public function getPromotionDiscount($entries = null)
	{
		if ($entries === null) {
			$entries = $this->getAvailableEntries();
		}

		// zero all entry promotion discounts. This prevents old values from
		// remaining when the promotion is removed.
		foreach ($entries as $entry) {
			$entry->promotion_discount = 0;
		}

		$promotion = null;

		if (isset($this->app->session->promotion)) {
			$promotion = $this->app->session->promotion;
		} else if ($this->promotion instanceof PromoPromotion) {
			$promotion = $this->promotion;
		}

		if ($promotion === null) {
			return 0;
		}

		$promotion->setDatabase($this->app->db);

		// sort entries by most expensive items first
		// (needed for promotion maximum-quantity checks)
		usort($entries, array($this, 'compareEntriesByPrice'));

		$discount = 0;
		$discount_quantity = 0;
		$discountable_item_total = 0;
		$discountable_entries = array();

		foreach ($entries as $entry) {
			if ($promotion->isCartEntryDiscountable($entry) &&
				($discount_quantity < $promotion->maximum_quantity ||
					$promotion->maximum_quantity === null)) {

				$discountable_entries[] = $entry;

				// if the cart entry's quantity is greater than the maxmimum
				// allowed by the promotion, only discount for quantity
				// less than the maximum promotion quantity
				if ($promotion->maximum_quantity !== null &&
					$discount_quantity + $entry->getQuantity() >
						$promotion->maximum_quantity) {

					$discountable_item_total +=
						$this->getPromotionDiscountableAmount(
							$promotion,
							$entry,
							($promotion->maximum_quantity - $discount_quantity));
				} else {
					$discountable_item_total +=
						$this->getPromotionDiscountableAmount(
							$promotion,
							$entry);
				}

				$discount_quantity += $entry->getQuantity();
			}
		}

		if ($promotion->isFixedDiscount()) {
			// Fixed amount promotions. These need to be pro-rated across
			// the entries for reporting purposes.

			if ($discountable_item_total > 0) {
				// order discount can't be more than the
				// discountable_item_total
				$discount = min(
					$discountable_item_total,
					$promotion->discount_amount
				);
			}

			// As we need to report per product, pro-rate the fixed discount
			// evenly among each entry it can apply to.
			if ($discount > 0 && $discountable_item_total > 0) {
				// ensure no discount greater than 1 (aka 100% off)
				$per_dollar_discount = min(
					1,
					($discount / $discountable_item_total)
				);

				foreach ($discountable_entries as $entry) {
					$entry->promotion_discount =
						($entry->getExtension() * $per_dollar_discount);
				}
			}
		} else {
			$discount_quantity = 0;
			foreach ($discountable_entries as $entry) {
				// if the cart entry's quantity is greater than the maxmimum
				// allowed by the promotion, only discount for quantity
				// less than the maximum promotion quantity
				if ($promotion->maximum_quantity !== null &&
					$discount_quantity + $entry->getQuantity() >
						$promotion->maximum_quantity) {

					$amount = $this->getPromotionDiscountableAmount(
						$promotion,
						$entry,
						$promotion->maximum_quantity - $discount_quantity);
				} else {
					$amount = $this->getPromotionDiscountableAmount(
						$promotion,
						$entry);
				}

				$entry->promotion_discount = SwatNumber::roundToEven(
					$amount * $promotion->discount_percentage, 2);

				$discount_quantity += $entry->getQuantity();
				$discount += $entry->promotion_discount;
			}
		}

		return $discount;
	}

	// }}}
	// {{{ protected function compareEntriesByPrice()

	protected function compareEntriesByPrice(StoreCartEntry $entry1,
		StoreCartEntry $entry2)
	{
		$price1 = $entry1->getCalculatedItemPrice();
		$price2 = $entry2->getCalculatedItemPrice();

		$sort = 0;

		if ($price1 == $price2) {
			$sort = ($entry1->id < $entry2->id) ? 1 : -1;
		} else {
			$sort = ($price1 < $price2) ? 1 : -1;
		}

		return $sort;
	}

	// }}}
	// {{{ protected function getPromotionDiscountableAmount()

	/**
	 * Gets the discountable total of the contents of this cart
	 *
	 * @param PromoPromotion $promotion Promotion to use
	 * @param array $entry Cart entry
	 * @param integer $quantity Quantity
	 *
	 * @return double the discountable total of this cart's contents.
	 */
	protected function getPromotionDiscountableAmount(
		PromoPromotion $promotion, $entry, $quantity = null)
	{
		if ($quantity === null) {
			$total = $entry->getExtension();
		} else {
			$total = $entry->getCalculatedItemPrice() * $quantity;
		}

		return $total;
	}

	// }}}
}

?>
