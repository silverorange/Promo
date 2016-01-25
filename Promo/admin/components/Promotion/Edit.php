<?php

require_once 'Admin/pages/AdminObjectEdit.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * Edit page for promotions
 *
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionEdit extends AdminObjectEdit
{
	// {{{ protected function getObjectClass()

	protected function getObjectClass()
	{
		return 'PromoPromotion';
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/edit.xml';
	}

	// }}}
	// {{{ protected function getObjectUiValueNames()

	protected function getObjectUiValueNames()
	{
		return array(
			'title',
			'public_note',
			'start_date',
			'end_date',
			'discount_amount',
			'discount_percentage',
			'maximum_quantity',
			'instance',
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkInstance();
		$this->initFlydowns();
	}

	// }}}
	// {{{ protected function checkInstance()

	protected function checkInstance()
	{
		$instance = $this->app->getInstance();
		$promotion = $this->getObject();

		if (
			$instance instanceof SiteInstance &&
			!(
				$promotion->instance instanceof SiteInstance &&
				$promotion->instance->id === $instance->id
			)
		) {
			throw new AdminNotFoundException(
				sprintf(
					'Incorrect instance for promotion ‘%s’.',
					$promotion->id
				)
			);
		}
	}

	// }}}
	// {{{ protected function initFlydowns()

	protected function initFlydowns()
	{
		if ($this->app->isMultipleInstanceAdmin()) {
			$this->ui->getWidget('instance')->addOptionsByArray(
				SwatDB::getOptionArray(
					$this->app->db,
					'Instance',
					'title',
					'id',
					'title'
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

		if ($start_date instanceof SwatDate &&
			$end_date instanceof SwatDate &&
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
	// {{{ protected function updateObject()

	protected function updateObject()
	{
		parent::updateObject();

		if ($this->isNew() &&
			$this->app->getInstance() instanceof SiteInstance) {
			$this->getObject()->instance = $this->app->getInstance();
		}
	}

	// }}}
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		return sprintf(
			Promo::_('Promotion “%s” has been saved.'),
			$this->getObject()->title
		);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(
			sprintf(
				'Promotion/Details?id=%s',
				$this->getObject()->id
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

		if (!$this->isNew()) {
			$promotion = $this->getObject();
			$last = $this->navbar->popEntry();

			$this->navbar->createEntry(
				$promotion->title,
				sprintf(
					'Promotion/Details?id=%s',
					$promotion->id
				)
			);

			$this->navbar->addEntry($last);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-promotion-edit.css'
		);
	}

	// }}}
}

?>
