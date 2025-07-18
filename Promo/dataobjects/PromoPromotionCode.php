<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property int            $id
 * @property ?string        $code
 * @property ?SwatDate      $createdate
 * @property ?SwatDate      $used_date
 * @property ?bool          $limited_use
 * @property PromoPromotion $promotion
 */
class PromoPromotionCode extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Code for lookup.
     *
     * @var string
     */
    public $code;

    /**
     * Date the code was created.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * Used date of this code (limited only).
     *
     * @var SwatDate
     */
    public $used_date;

    /**
     * Whether this code can only be used once.
     *
     * @var bool
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
            SwatDBClassMap::get(PromoPromotion::class)
        );
    }
}
