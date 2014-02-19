<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * Edit page for promotions
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/edit.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$this->initPromotion();
		$this->initFlydowns();
	}

	// }}}
	// {{{ protected function initPromotion()

	protected function initPromotion()
	{
		$class_name = SwatDBClassMap::get('PromoPromotion');
		$this->promotion = new $class_name();
		$this->promotion->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->promotion->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A promotion with the id of ‘%s’ does not exist',
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
	}

	// }}}
	// {{{ protected function initFlydowns()

	protected function initFlydowns()
	{
		if ($this->app->hasModule('SiteMultipleInstanceModule')) {
			$instance_id = $this->app->getInstanceId();
			if ($instance_id !== null) {
				$where = sprintf(
					'id = %s',
					$this->app->db->quote($instance_id, 'integer')
				);
			}

			$this->ui->getWidget('instance')->addOptionsByArray(
				SwatDB::getOptionArray(
					$this->app->db,
					'Instance',
					'title',
					'id',
					'title',
					$where
				)
			);
		} else {
			$this->ui->getWidget('instance_field')->visible = false;
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$start_date = $this->ui->getWidget('start_date')->value;
		$end_date   = $this->ui->getWidget('end_date')->value;

		if ($start_date !== null && $end_date !== null &&
			SwatDate::compare($start_date, $end_date) > 0) {

			$message = new SwatMessage(
				sprintf(
					Promo::_(
						'%1$sStart Date%2$s must occur before '.
						'%1$sEnd Date%2$s.'
					),
					'<strong>',
					'</strong>'
				),
				'error'
			);

			$message->content_type = 'text/xml';

			$container = $this->ui->getWidget('active_period_container');
			$container->display_messages = true;
			$container->addMessage($message);
		}

		$amount  = $this->ui->getWidget('discount_amount');
		$percent = $this->ui->getWidget('discount_percentage');

		if (!$amount->hasMessage() &&
			!$percent->hasMessage() &&
			$amount->value === null &&
			$percent->value === null) {
			$message = new SwatMessage(
				sprintf(
					Promo::_(
						'Either the %1$sFixed Amount Discount%2$s or the '.
						'%1$sPercentage Discount%2$s is reqired.'
					),
					'<strong>',
					'</strong>'
				),
				'error'
			);

			$message->content_type = 'text/xml';

			$container = $this->ui->getWidget('discount_container');
			$container->display_messages = true;
			$container->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updatePromotion();
		$this->promotion->save();

		$this->app->messages->add(
			new SwatMessage(
				sprintf(
					Promo::_('Promotion “%s” has been saved.'),
					$this->promotion->title
				)
			)
		);
	}

	// }}}
	// {{{ protected function updatePromotion()

	protected function updatePromotion()
	{
		$values = $this->ui->getValues(
			array(
				'title',
				'public_note',
				'start_date',
				'end_date',
				'discount_amount',
				'discount_percentage',
				'maximum_quantity',
				'instance',
			)
		);

		if ($values['start_date'] !== null) {
			$start_date = $values['start_date'];
			$start_date->setTZ($this->app->default_time_zone);
			$start_date->toUTC();
			$values['start_date'] = $start_date->getDate();
		}

		if ($values['end_date'] !== null) {
			$end_date = $values['end_date'];
			$end_date->setTZ($this->app->default_time_zone);
			$end_date->toUTC();
			$values['end_date'] = $end_date->getDate();
		}

		$this->promotion->title               = $values['title'];
		$this->promotion->public_note         = $values['public_note'];
		$this->promotion->instance            = $values['instance'];
		$this->promotion->start_date          = $values['start_date'];
		$this->promotion->end_date            = $values['end_date'];
		$this->promotion->discount_amount     = $values['discount_amount'];
		$this->promotion->discount_percentage = $values['discount_percentage'];
		$this->promotion->maximum_quantity    = $values['maximum_quantity'];
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(
			sprintf(
				'Promotion/Details?id=%s',
				$this->promotion->id
			)
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);

		$this->ui->getWidget('active_period_container')->note = sprintf(
			Promo::_('Start Date and End Date are in %s and are inclusive.'),
			$date->formatLikeIntl('z')
		);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->promotion->id !== null) {
			$last = $this->navbar->popEntry();

			$this->navbar->addEntry(
				new SwatNavBarEntry(
					$this->promotion->title,
					sprintf(
						'Promotion/Details?id=%s',
						$this->promotion->id
					)
				)
			);

			$this->navbar->addEntry($last);
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->promotion));

		$this->ui->getWidget('instance')->value =
			$this->promotion->getInternalValue('instance');

		$start_date = $this->ui->getWidget('start_date');
		if ($start_date->value !== null) {
			$start_date->value->convertTZ($this->app->default_time_zone);
		}

		$end_date = $this->ui->getWidget('end_date');
		if ($end_date->value !== null) {
			$end_date->value->convertTZ($this->app->default_time_zone);
		}

	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-promotion-edit.css',
			Promo::PACKAGE_ID
		);
	}

	// }}}
}

?>
