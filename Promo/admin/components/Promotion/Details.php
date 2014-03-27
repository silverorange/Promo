<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Promo/dataobjects/PromoPromotion.php';
require_once 'Promo/dataobjects/PromoPromotionCodeWrapper.php';

/**
 * Details page for a promotion
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionDetails extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/details.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$this->initPromotion();
	}

	// }}}
	// {{{ protected function initPromotion()

	protected function initPromotion()
	{
		$this->id = SiteApplication::initVar('id');
		$promotion_class = SwatDBClassMap::get('PromoPromotion');

		$this->promotion = new $promotion_class();
		$this->promotion->setDatabase($this->app->db);

		if (!$this->promotion->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(
					'A promotion with an id of ‘%d’ does not exist.',
					$this->id
				)
			);
		}

		$instance_id = $this->app->getInstanceId();
		if ($instance_id !== null &&
			$this->promotion->instance->id !== $instance_id) {
			throw new AdminNotFoundException(
				sprintf(
					'Incorrect instance for promotion ‘%s’.',
					$this->id
				)
			);
		}
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$this->ui->getWidget('promotion_code_pager')->process();
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->checked_items);
		$message = null;

		switch ($actions->selected->id) {
			case 'promotion_code_delete':
				$this->app->replacePage('Promotion/DeletePromotionCode');
				$this->app->getPage()->setItems($view->checked_items);
				break;
		}

		if ($message !== null) {
			$this->app->messages->add($message);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildToolbars();

		$this->title = $this->promotion->title;

		$frame = $this->ui->getWidget('details_frame');
		$frame->subtitle = $this->title;

		$view = $this->ui->getWidget('details_view');
		$view->data = $this->getDetailsStore();

		if ($this->promotion->start_date instanceof SwatDate ||
			$this->promotion->end_date instanceof SwatDate) {
			$field = $view->getField('active_period_field');
			$field->getRenderer('active_period_note_renderer')->visible = true;
		}

		if ($this->app->isMultipleInstanceAdmin()) {
			$instance_field = $view->getField('instance_field');
			$instance_field->visible = true;
		}

		// set the timezone on the used date column;
		$used_column =
			$this->ui->getWidget('promotion_code_view')->getColumn('used_date');

		$used_renderer = $used_column->getRendererByPosition();
		$used_renderer->display_time_zone = $this->app->default_time_zone;

		$this->buildPromotionCodeText();

		if ($this->promotion->getUnusedCodeCount() === 0) {
			$message = new SwatMessage(
				Promo::_('Wait! This promotion can’t be used!'),
				'warning'
			);

			$message->secondary_content = sprintf(
				Promo::_(
					'This promotion doesn’t have any available promotion '.
					'codes. <a href="%s">Add</a> or <a href="%s">generate</a> '.
					'promotion codes so this promotion can be entered by '.
					'customers.'
				),
				sprintf(
					'Promotion/PromotionCodeEdit?promotion=%s',
					$this->id
				),
				sprintf(
					'Promotion/GenerateCodes?promotion=%s',
					$this->id
				)
			);

			$message->content_type = 'text/xml';

			$this->ui->getWidget('warning_message_display')->add(
				$message,
				SwatMessageDisplay::DISMISS_OFF
			);
		}
	}

	// }}}
	// {{{ protected function buildToolbars()

	protected function buildToolbars()
	{
		foreach ($this->ui->getRoot()->getDescendants('SwatToolbar') as
			$toolbar) {
			$toolbar->setToolLinkValues($this->promotion->id);
		}
	}

	// }}}
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore()
	{
		$ds = new SwatDetailsStore($this->promotion);

		$ds->show_discount_amount = ($this->promotion->discount_amount > 0);

		$ds->valid_dates = $this->promotion->getValidDatesWithTz(
			$this->app->default_time_zone
		);

		$ds->note_edit_link = sprintf(
			'Promotion/NoteEdit?id=%s',
			$this->promotion->id
		);

		$ds->order_summary = $this->getOrderSummary();
		$ds->has_orders = ($ds->order_summary !== null);

		return $ds;
	}

	// }}}
	// {{{ protected function getOrderSummary()

	protected function getOrderSummary()
	{
		$sql = sprintf(
			'select count(1) as num_orders,
				sum(promotion_total) as promotion_total, sum(total) as total
			from Orders
			where cancel_date is null and promotion_code in (
				select code from PromotionCode where promotion = %s
			)',
			$this->app->db->quote($this->promotion->id, 'integer')
		);

		$row = SwatDB::queryRow(
			$this->app->db,
			$sql,
			array('integer', 'float', 'float')
		);

		$locale = SwatI18NLocale::get();

		if ($row->num_orders === 0) {
			$summary = null;
		} else {
			if ($row->promotion_total == 0) {
				$formatted_roi = Promotion::_('∞');
			} else {
				$formatted_roi = sprintf(
					Promo::_('%s%%'),
					$locale->formatNumber(
						SwatNumber::roundToEven(
							(
								($row->total - $row->promotion_total) /
								$row->promotion_total
							) * 100,
							2
						),
						2
					)
				);
			}

			$summary = sprintf(
				Promo::ngettext(
					'%s order, %s return on investment',
					'%s orders, %s return on investment',
					$row->num_orders
				),
				$locale->formatNumber($row->num_orders),
				$formatted_roi
			);
		}

		return $summary;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'promotion_code_view':
			return $this->getPromotionCodeTableModel($view);
		}
	}

	// }}}
	// {{{ protected function getPromotionCodeTableModel()

	protected function getPromotionCodeTableModel(SwatTableView $view)
	{
		$sql = sprintf(
			'select count(id) from PromotionCode
			where promotion = %s',
			$this->app->db->quote($this->promotion->id, 'integer')
		);

		$pager = $this->ui->getWidget('promotion_code_pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select * from PromotionCode
			where promotion = %s
			order by %s',
			$this->app->db->quote($this->promotion->id, 'integer'),
			$this->getOrderByClause($view, 'code')
		);

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$promotion_codes = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('PromoPromotionCodeWrapper')
		);

		if (count($promotion_codes) > 0) {
			$this->ui->getWidget('results_message')->content_type = 'txt/xml';
			$this->ui->getWidget('results_message')->content = sprintf(
				'<p id="results_message">%s</p>',
				$pager->getResultsMessage(
					Promo::_('promotion code'),
					Promo::_('promotion codes')
				)
			);
		}

		$store = new SwatTableStore();
		foreach ($promotion_codes as $code) {
			$ds = new SwatDetailsStore($code);
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function buildPromotionCodeText()

	protected function buildPromotionCodeText()
	{
		$sql = sprintf(
			'select * from PromotionCode
			where promotion = %s and (
				used_date is null or limited_use = false
			)
			order by limited_use, code',
			$this->app->db->quote($this->promotion->id, 'integer')
		);

		$codes = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('PromoPromotionCodeWrapper')
		);

		if (count($codes) > 0) {
			$unlimited_code_text = array();
			$limited_code_text = array();
			foreach ($codes as $code) {
				if ($code->limited_use) {
					$limited_code_text[] = $code->code;
				} else {
					$unlimited_code_text[] = $code->code;
				}
			}

			$unlimited_code_text = implode(', ', $unlimited_code_text);
			$limited_code_text = implode(', ', $limited_code_text);

			if ($unlimited_code_text != '' && $limited_code_text != '') {
				$code_text = sprintf(
					Promo::_("Unlimited Use:\n%s\n\nOne-Time Use:\n%s"),
					$unlimited_code_text,
					$limited_code_text
				);
			} elseif ($unlimited_code_text != '') {
				$code_text = sprintf(
					Promo::_("Unlimited Use:\n%s"),
					$unlimited_code_text
				);
			} elseif ($limited_code_text != '') {
				$code_text = sprintf(
					Promo::_("One-Time Use:\n%s"),
					$limited_code_text
				);
			} else {
				$code_text = Promo::_('None');
			}

			$this->ui->getWidget('promotion_code_summary')->visible = true;
			$summary = $this->ui->getWidget('promotion_code_summary_text');
			$summary->content = sprintf(
				'<p>%s</p>',
				nl2br(SwatString::minimizeEntities($code_text))
			);
			$summary->content_type = 'text/xml';
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->createEntry($this->promotion->title);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-promotion-details.css'
		);
	}

	// }}}
}

?>
