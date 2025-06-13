<?php

/**
 * Edit page for promotion codes
 *
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionPromotionCodeEdit extends AdminObjectEdit
{


	/**
	 * @var PromoPromotion
	 */
	protected $promotion;




	protected function getObjectClass()
	{
		return 'PromoPromotionCode';
	}




	protected function getUiXml()
	{
		return __DIR__.'/promotion-code-edit.xml';
	}




	protected function getObjectUiValueNames()
	{
		return array(
			'code',
			'limited_use',
		);
	}



	// init phase


	protected function initInternal()
	{
		parent::initInternal();

		$this->initPromotion();
		$this->checkInstance();
	}




	protected function initPromotion()
	{
		if ($this->getObject()->promotion instanceof PromoPromotion) {
			$this->promotion = $this->getObject()->promotion;
		} else {
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
		}
	}




	protected function checkInstance()
	{
		$instance = $this->app->getInstance();
		if (
			$instance instanceof SiteInstance &&
			!(
				$this->promotion->instance instanceof SiteInstance &&
				$this->promotion->instance->id === $instance->id
			)
		) {
			throw new AdminNotFoundException(
				sprintf(
					'Incorrect instance for promotion ‘%s’.',
					$this->promotion->id
				)
			);
		}
	}



	// process phase


	protected function validate(): void
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

		$sql = 'select count(1) from PromotionCode
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

		$count = SwatDB::queryOne($this->app->db, $sql);

		return ($count === 0);
	}




	protected function updateObject()
	{
		parent::updateObject();

		$promotion_code = $this->getObject();
		if ($this->isNew()) {
			$promotion_code->promotion = $this->promotion;
		}

		// force all codes to be lowercase
		$promotion_code->code = mb_strtolower($promotion_code->code);

		// unset the used date if the promotion is no longer limited use.
		if ($promotion_code->limited_use === false) {
			$promotion_code->used_date = null;
		}
	}




	protected function getSavedMessagePrimaryContent()
	{
		return sprintf(
			Promo::_('Promotion Code ‘%s’ has been saved.'),
			$this->getObject()->code
		);
	}




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



	// build phase


	protected function buildForm()
	{
		parent::buildForm();

		// if it's new, add the promotion id to the form.
		if ($this->isNew()) {
			$this->ui->getWidget('edit_form')->addHiddenField(
				'promotion',
				$this->promotion->id
			);
		} else {
			$this->ui->getWidget('submit_another_button')->visible = false;
			$this->ui->getWidget('submit_button')->title = 'Done';
		}
	}




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

		if ($this->isNew()) {
			$title = Promo::_('New Promotion Code');
		} else {
			$title = Promo::_('Edit Promotion Code');
		}

		$this->navbar->createEntry($title);
	}


}

?>
