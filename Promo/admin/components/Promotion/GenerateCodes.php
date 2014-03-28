<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Swat/SwatMessage.php';
require_once 'Text/Password.php';
require_once 'Promo/dataobjects/PromoPromotion.php';
require_once 'Promo/dataobjects/PromoPromotionCode.php';

/**
 * Page to generate a set of promotion codes
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionGenerateCodes extends AdminDBEdit
{
	// {{{ class constants

	const LENGTH = 8;

	// }}}
	// {{{ protected properties

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/generate-codes.xml';
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

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(
			array(
				'prefix',
				'quantity',
				'limited_use',
			)
		);

		$prefix = strtolower($values['prefix']);

		$now = new SwatDate();
		$now->toUTC();

		$values_string = sprintf(
			'(%s, %s, %s, %%s)',
			$this->app->db->quote($this->promotion->id, 'integer'),
			$this->app->db->quote($values['limited_use'], 'boolean'),
			$this->app->db->quote($now, 'date')
		);

		$codes = Text_Password::createMultiple(
			$values['quantity'],
			self::LENGTH
		);

		$codes_out = array();
		foreach ($codes as $code) {
			if ($prefix != '') {
				$code = sprintf('%s-%s', $prefix, $code);
			}
			$codes_out[] = $code;
		}

		$codes = $this->verifyUniqueness($codes_out, $prefix);

		// insert values
		$values_out = array();
		foreach ($codes as $code) {
			$values_out[] = sprintf(
				$values_string,
				$this->app->db->quote($code, 'text')
			);
		}

		$sql = 'insert into PromotionCode
			(promotion, limited_use, createdate, code)
			values %s';

		$sql = sprintf(
			$sql,
			implode($values_out, ',')
		);

		$count = SwatDB::exec($this->app->db, $sql);
		$locale = SwatI18NLocale::get();

		$this->app->messages->add(
			new SwatMessage(
				sprintf(
					Promo::ngettext(
						'One promotion code has been generated.',
						'%s promotion codes have been generated.',
						$count
					),
					$locale->formatNumber($count)
				)
			)
		);
	}

	// }}}
	// {{{ protected function verifyUniqueness()

	protected function verifyUniqueness($codes, $prefix = null)
	{
		$lower_codes = array_map('strtolower', $codes);

		$instance_where = ($this->promotion->instance instanceof SiteInstance)
			? sprintf(
				'Promotion.instance = %s',
				$this->app->db->quote(
					$this->promotion->instance->id,
					'integer'
				)
			)
			: '1 = 1';

		$sql = sprintf(
			'select PromotionCode.code
				from PromotionCode
				inner join Promotion on PromotionCode.promotion = Promotion.id
				where lower(PromotionCode.code) in (%s) and %s',
			$this->app->db->datatype->implodeArray($lower_codes, 'text'),
			$instance_where
		);

		$existing_codes = SwatDB::query($this->app->db, $sql);

		$new_codes = array();
		$old_codes = array();

		foreach ($existing_codes as $existing_code) {
			$new_code = $this->generateCode($prefix);
			// Make sure the new code is unique for any new generated codes as
			// well.
			while (in_array($new_code, $codes) ||
				in_array($new_code, $new_codes)) {
				$new_code = $this->generateCode($prefix);
			}

			$new_codes[] = $new_code;
			$old_codes[] = $existing_code->code;
		}

		$codes = array_diff($codes, $old_codes);
		$codes = array_merge($codes, $new_codes);

		if (count($new_codes) > 0) {
			$codes = $this->verifyUniqueness($codes, $prefix);
		}

		return $codes;
	}

	// }}}
	// {{{ protected function generateCode()

	protected function generateCode($prefix = null)
	{
		$code = Text_Password::create(self::LENGTH);
		if ($prefix != '') {
			$code = sprintf('%s-%s', $prefix, $code);
		}

		return $code;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('edit_frame')->title =
			Promo::_('Generate Promotion Codes');
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$this->ui->getWidget('edit_form')->addHiddenField(
			'promotion',
			$this->promotion->id
		);
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

		$this->navbar->createEntry(
			Promo::_('Generate Promotion Codes')
		);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		// do nothing;
	}

	// }}}
}

?>
