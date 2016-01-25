<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Promotions
 *
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @todo      Enforce instance
 */
class PromoPromotionDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from Promotion where id in (%s);';

		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$locale = SwatI18NLocale::get();

		$message = new SwatMessage(
			sprintf(
				Promo::ngettext(
					'One promotion has been deleted.',
					'%s promotions have been deleted.',
					$num
				),
				$locale->formatNumber($num)
			)
		);

		$this->app->messages->add($message);
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
			Promo::_('promotion'),
			Promo::_('promotions')
		);

		$dep->entries = AdminListDependency::queryEntries(
			$this->app->db,
			'Promotion',
			'integer:id',
			null,
			'text:title',
			'id',
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
}

?>
