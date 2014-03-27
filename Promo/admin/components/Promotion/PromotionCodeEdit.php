<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Promo/dataobjects/PromoPromotion.php';
require_once 'Promo/dataobjects/PromoPromotionCode.php';

/**
 * Edit page for promotion codes
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionPromotionCodeEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	/**
	 * @var PromoPromotionCode
	 */
	protected $promotion_code;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/promotion-code-edit.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$this->initPromotionCode();
		$this->initPromotion();
	}

	// }}}
	// {{{ protected function initPromotionCode()

	protected function initPromotionCode()
	{
		$class_name = SwatDBClassMap::get('PromoPromotionCode');
		$this->promotion_code = new $class_name();
		$this->promotion_code->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->promotion_code->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A promotion code with the id of ‘%s’ does not exist',
						$this->id
					)
				);
			}

			$this->promotion = $this->promotion_code->promotion;

			$instance_id = $this->app->getInstanceId();
			if ($instance_id !== null &&
				$this->promotion->instance->id !== $instance_id) {
				throw new AdminNotFoundException(
					sprintf(
						'Incorrect instance for promotion code ‘%s’.',
						$this->id
					)
				);
			}
		}
	}

	// }}}
	// {{{ protected function initPromotion()

	protected function initPromotion()
	{
		// check to see if promotion is set by loading promotion code first.
		if (!$this->promotion instanceof PromoPromotion) {
			$promotion_id = SiteApplication::initVar('promotion');

			$class_name = SwatDBClassMap::get('PromoPromotion');
			$this->promotion = new $class_name();
			$this->promotion->setDatabase($this->app->db);

			if (!$this->promotion->load($promotion_id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A promotion with the id of ‘%s’ does not exist',
						$promotion_id
					)
				);
			}

			$instance_id = $this->app->getInstanceId();
			if ($instance_id !== null &&
				$this->promotion->instance->id !== $instance_id) {
				throw new AdminNotFoundException(
					sprintf(
						'Incorrect instance for promotion ‘%s’.',
						$promotion_id
					)
				);
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$code = $this->ui->getWidget('code')->value;

		if (!$this->validateCode($code)) {
			$message = new SwatMessage(
				Promo::_('Promotion Code already exists and must be unique.'),
				'error'
			);

			$this->ui->getWidget('code')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateCode()

	protected function validateCode($code)
	{
		$instance_where = ($this->promotion->instance instanceof SiteInstance)
			? sprintf(
				'Promotion.instance = %s',
				$this->app->db->quote(
					$this->promotion->instance->id,
					'integer'
				)
			)
			: '1 = 1';

		$sql = 'select code from PromotionCode
			inner join Promotion on Promotion.id = PromotionCode.promotion
			where %s and lower(PromotionCode.code) = lower(%s) and
				PromotionCode.id %s %s';

		$sql = sprintf(
			$sql,
			$instance_where,
			$this->app->db->quote($code, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer')
		);

		$rs = SwatDB::query($this->app->db, $sql);

		return (count($rs) === 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updatePromotionCode();
		$this->promotion_code->save();

		$this->app->messages->add(
			new SwatMessage(
				sprintf(
					'Promotion Code ‘%s’ has been saved.',
					$this->promotion_code->code
				)
			)
		);
	}

	// }}}
	// {{{ protected function updatePromotionCode()

	protected function updatePromotionCode()
	{
		$values = $this->ui->getValues(
			array(
				'code',
				'limited_use',
			)
		);

		if ($this->promotion_code->id === null) {
			$this->promotion_code->promotion = $this->promotion;

			$now = new SwatDate();
			$now->toUTC();
			$this->promotion_code->createdate = $now;
		}

		$this->promotion_code->code        = strtolower($values['code']);
		$this->promotion_code->limited_use = $values['limited_use'];

		// unset the used date if the promotion is no longer limited use.
		if ($this->promotion_code->limited_use === false) {
			$this->promotion_code->used_date = null;
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		if ($this->ui->getWidget('submit_another_button')->hasBeenClicked()) {
			$uri = sprintf(
				'Promotion/PromotionCodeEdit?promotion=%s',
				$this->promotion->id
			);
		} else {
			$uri = sprintf(
				'Promotion/Details?id=%s',
				$this->promotion->id
			);
		}

		$this->app->relocate($uri);
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		// if it's new, add the promotion id to the form.
		if ($this->promotion_code->id === null) {
			$this->ui->getWidget('edit_form')->addHiddenField(
				'promotion',
				$this->promotion->id
			);
		} else {
			$this->ui->getWidget('submit_another_button')->visible = false;
			$this->ui->getWidget('submit_button')->title = 'Done';
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$last = $this->navbar->popEntry();
		$this->navbar->createEntry(
			$this->promotion->title,
			sprintf(
				'Promotion/Details?id=%s',
				$this->promotion->id
			)
		);

		if ($this->promotion_code->id === null) {
			$title = Promo::_('New Promotion Code');
		} else {
			$title = Promo::_('Edit Promotion Code');
		}

		$this->navbar->createEntry($title);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->promotion_code));
	}

	// }}}
}

?>
