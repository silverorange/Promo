<?php

require_once 'Text/Password.php';
require_once 'Site/SiteApplication.php';
require_once 'SwatDB/SwatDB.php';

/**
 * @package   Promo
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionCodeGenerator
{
	// {{{ class constants

	const LENGTH = 8;

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function getCodes()

	public function getCodes(PromoPromotion $promotion, $quantity, $prefix = '')
	{
		$prefix = strtolower($prefix);

		$codes = Text_Password::createMultiple(
			$quantity,
			self::LENGTH
		);

		if ($prefix != '') {
			$codes = array_map(
				function ($code) use ($prefix) {
					return sprintf(
						'%s-%s',
						$prefix,
						$code
					);
				},
				$codes
			);
		}

		return $this->verifyUniqueness($promotion, $codes, $prefix);
	}

	// }}}
	// {{{ protected function verifyUniqueness()

	protected function verifyUniqueness(PromoPromotion $promotion, $codes,
		$prefix = null)
	{
		$lower_codes = array_map('strtolower', $codes);

		$instance_where = ($promotion->instance instanceof SiteInstance)
			? sprintf(
				'Promotion.instance = %s',
				$this->app->db->quote(
					$promotion->instance->id,
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
}

?>
