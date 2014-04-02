<?php

require_once 'Admin/pages/AdminObjectEdit.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionNoteEdit extends AdminObjectEdit
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
		return 'Promo/admin/components/Promotion/note-edit.xml';
	}

	// }}}
	// {{{ protected function getObjectPropertyWidgetMapping()

	protected function getObjectPropertyWidgetMapping()
	{
		return array(
			'notes',
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->checkInstance();
	}

	// }}}
	// {{{ protected function checkInstance()

	protected function checkInstance()
	{
		$instance_id = $this->app->getInstanceId();
		$promotion = $this->getObject();

		if ($instance_id !== null &&
			$promotion->instance->id !== $instance_id) {
			throw new AdminNotFoundException(
				sprintf(
					'Incorrect instance for promotion ‘%s’.',
					$promotion->id
				)
			);
		}
	}

	// }}}

	// process phase
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		return Promo::_('Note has been saved.');
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$promotion = $this->getObject();
		$this->navbar->popEntry();
		$this->navbar->createEntry(
			$promotion->title,
			sprintf(
				'Promotion/Details?id=%s',
				$promotion->id
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
			'packages/promo/admin/styles/promo-promotion-note-edit.css'
		);
	}

	// }}}
}

?>
