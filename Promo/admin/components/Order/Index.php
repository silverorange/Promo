<?php

/**
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoOrderIndex extends StoreOrderIndex
{
	// {{{ protected function getAdditionalSearchFieldsUiXmlFiles()

	protected function getAdditionalSearchFieldsUiXmlFiles()
	{
		return array(
			__DIR__.'/search-promotion-fields.xml',
		);
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where = parent::getWhereClause();

		$promotion_code = $this->ui->getWidget('search_promotion_code')->value;
		if (trim($promotion_code) != '') {
			$clause = new AdminSearchClause('text:promotion_code');
			$clause->table = 'Orders';
			$clause->value = $promotion_code;
			$clause->operator = AdminSearchClause::OP_CONTAINS;
			$where.= $clause->getClause($this->app->db);
		}

		return $where;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-order-index.css'
		);
	}

	// }}}
}

?>
