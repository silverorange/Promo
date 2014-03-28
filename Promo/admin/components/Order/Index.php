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

		$this->promotion_code = new SwatEntry('search_promotion_code');

		$code_field = new SwatFormField();
		$code_field->title = Promo::_('Promotion Code');
		$code_field->id = 'promo_code';
		$code_field->add($this->promotion_code);

		$promotion_fieldset = new SwatFieldset('promotion_fields');
		$promotion_fieldset->title = Promo::_('Promotion');
		$promotion_fieldset->add($code_field);

		$this->ui->getWidget('search_form')->insertBefore(
			$promotion_fieldset,
			$this->ui->getWidget('search_form')->getFirstDescendant(
				'SwatFooterFormField'
			)
		);
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
