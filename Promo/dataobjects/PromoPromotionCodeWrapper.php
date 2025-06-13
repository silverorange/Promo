<?php

/**
 * A recordset wrapper class for PromoPromotionCode objects.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       PromoPromotionCode
 */
class PromoPromotionCodeWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();

        $this->row_wrapper_class = SwatDBClassMap::get('PromoPromotionCode');
        $this->index_field = 'id';
    }
}
