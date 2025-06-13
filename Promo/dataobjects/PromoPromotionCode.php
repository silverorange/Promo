<?php

/**
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionCode extends SwatDBDataObject
{


	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Code for lookup
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Date the code was created
	 *
	 * @var SwtaDate
	 */
	public $createdate;

	/**
	 * Used date of this code (limited only)
	 *
	 * @var SwatDate
	 */
	public $used_date;

	/**
	 * Whether this code can only be used once
	 *
	 * @var boolean
	 */
	public $limited_use;




	protected function init()
	{
		parent::init();

		$this->table = 'PromotionCode';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
		$this->registerDateProperty('used_date');

		$this->registerInternalProperty(
			'promotion',
			SwatDBClassMap::get('PromoPromotion')
		);
	}


}

?>
