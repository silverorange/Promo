<?php

require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatFieldset.php';
require_once 'Store/admin/components/Order/Index.php';

/**
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoOrderIndex extends StoreOrderIndex
{
	// {{{ protected function getAdditionalSearchFieldsUiXmlFiles()

	protected function getAdditionalSearchFieldsUiXmlFiles()
	{
		return array(
			'Promo/admin/components/Order/search-promotion-fields.xml',
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
