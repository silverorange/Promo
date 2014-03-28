<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';
require_once 'Promo/dataobjects/PromoPromotionWrapper.php';

/**
 * Delete confirmation page for Promotion Codes
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 * @todo      Enforce instance security.
 */
class PromoPromotionDeletePromotionCode extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from PromotionCode where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$locale = SwatI18NLocale::get();

		$message = new SwatMessage(
			sprintf(
				Promo::ngettext(
					'One promotion code has been deleted.',
					'%d promotion codes have been deleted.',
					$num
				),
				$locale->formatNumber($num)
			)
		);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		// AdminDBDelete avoids relocating to the details page since in the
		// general case it may no longer exist. On this page we know it still
		// will exist so skip AdminDBDelete's relocate code and go back to the
		// details page.
		AdminDBConfirmation::relocate();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(
			Promo::_('promotion code'),
			Promo::_('promotion codes')
		);

		$dep->entries = AdminListDependency::queryEntries(
			$this->app->db,
			'PromotionCode',
			'integer:id',
			null,
			'text:code',
			'code',
			'id in ('.$item_list.')',
			AdminDependency::DELETE
		);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) === 0) {
			$this->switchToCancelButton();
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$promotion = $this->getPromotion();

		$last = $this->navbar->popEntry();
		$this->navbar->createEntry(
			$promotion->title,
			sprintf(
				'Promotion/Details?id=%s',
				$promotion->id
			)
		);

		$this->navbar->createEntry(
			Promo::_('Promotion Code Delete')
		);
	}

	// }}}
	// {{{ protected function getPromotion()

	protected function getPromotion()
	{
		$sql = sprintf(
			'select * from Promotion
			where id in (
				select promotion from PromotionCode where id in (%s)
			)',
			$this->getItemList('integer')
		);

		$promotions = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('PromoPromotionWrapper')
		);

		if (count($promotions) === 1) {
			$promotion = $promotions->getFirst();
		} else {
			$promotion = null;
		}

		return $promotion;
	}

	// }}}
}

?>
