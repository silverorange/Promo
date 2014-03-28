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
	// {{{ protected properties

	/**
	 * @var SwatEntry
	 */
	protected $promotion_code;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->addAdditionalSearchFields();
	}

	// }}}
	// {{{ protected function addAdditionalSearchFields()

	protected function addAdditionalSearchFields()
	{
		$ui_xml = $this->getAdditionalSearchFieldsUiXml();

		if ($ui_xml != '') {
			$additional_search_fields = new AdminUI();
			$additional_search_fields->loadFromXML($ui_xml);

			$this->ui->getWidget('search_form')->insertBefore(
				$additional_search_fields->getRoot(),
				$this->ui->getWidget('search_form')->getFirstDescendant(
					'SwatFooterFormField'
				)
			);
		}
	}

	// }}}
	// {{{ protected function getAdditionalSearchFieldsUiXml()

	protected function getAdditionalSearchFieldsUiXml()
	{
		return 'Promo/admin/components/Order/search-promotion-fields.xml';
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$where = parent::getWhereClause();

		$promotion_code = $this->promotion_code->value;
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
