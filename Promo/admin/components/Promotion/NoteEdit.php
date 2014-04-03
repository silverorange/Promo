<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionNoteEdit extends AdminDBEdit
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
		return 'Promo/admin/components/Promotion/note-edit.xml';
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
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$notes = $this->ui->getWidget('notes');
		$this->promotion->notes = $notes->value;
		$this->promotion->save();

		$this->app->messages->add($this->getSaveMessage());
	}

	// }}}
	// {{{ protected function getSaveMessage()

	protected function getSaveMessage()
	{
		return new SwatMessage(
			Promo::_('Note has been saved.')
		);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$notes = $this->ui->getWidget('notes');
		$notes->value = $this->promotion->notes;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();
		$this->navbar->createEntry(
			$this->promotion->title,
			sprintf(
				'Promotion/Details?id=%s',
				$this->promotion->id
			)
		);

		$this->navbar->createEntry(
			Promo::_('Edit Note')
		);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-admin-notices.css'
		);
	}

	// }}}
}

?>
