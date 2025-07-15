<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotion extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Title of the promotion.
     *
     * @var string
     */
    public $title;

    /**
     * Start date of promotion.
     *
     * @var SwatDate
     */
    public $start_date;

    /**
     * End date of promotion.
     *
     * @var SwatDate
     */
    public $end_date;

    /**
     * A fixed amount discount.
     *
     * @var float
     */
    public $discount_amount;

    /**
     * A percentage based discount.
     *
     * @var float
     */
    public $discount_percentage;

    /**
     * Only visible in the admin.
     *
     * @var string
     */
    public $notes;

    /**
     * Public note, shown to customers.
     *
     * @var string
     */
    public $public_note;

    /**
     * Maximum quantity of items for purchase.
     *
     * @var int
     */
    public $maximum_quantity;

    /**
     * Code used to look up the promotion.
     *
     * @var PromotionCode
     */
    protected $code;

    public function isFixedDiscount()
    {
        return $this->discount_amount !== null;
    }

    public function isPercentageDiscount()
    {
        return !$this->isFixedDiscount();
    }

    public function is100PercentDiscount()
    {
        return
            $this->isPercentageDiscount()
            && abs($this->discount_percentage - 1.00) < 0.009;
    }

    public function isCartEntryDiscountable(PromoCartEntry $cart_entry)
    {
        return true;
    }

    public function getNote()
    {
        if ($this->isFixedDiscount()) {
            $note = null;
        } else {
            $note = sprintf(
                Promo::_('Save %s%%'),
                $this->discount_percentage * 100
            );
        }

        return $note;
    }

    public function displayDetails(SiteApplication $app)
    {
        printf(
            '<h4>%s: <strong>%s</strong></h4>',
            Promo::_('Promotion'),
            SwatString::minimizeEntities($this->getCode())
        );

        $this->displayDiscount($app);
    }

    public function displayDiscount(SiteApplication $app)
    {
        $p = new SwatHtmlTag('p');
        $p->setContent($this->getDiscountMessage($app), 'text/xml');
        $p->display();

        if ($this->public_note !== null) {
            $div_tag = new SwatHtmlTag('div');
            $div_tag->class = 'promo-promotion-note';
            $div_tag->setContent($this->public_note);
            $div_tag->display();
        }
    }

    public function getFormattedDiscount(SiteApplication $app)
    {
        $locale = SwatI18NLocale::get();

        if ($this->isFixedDiscount()) {
            $formatted_amount = $locale->formatCurrency($this->discount_amount);
        } else {
            // Since percentage discount is the fallback cover the edge case
            // where the stored value is null and treat as a 0% discount.
            $displayed_percentage = ($this->discount_percentage === null)
                ? 0
                : ($this->discount_percentage * 100);

            $formatted_amount = $locale->formatNumber($displayed_percentage) .
                '%';
        }

        return $formatted_amount;
    }

    /**
     * Gets an XHTML snippet describing this promotion's discount.
     *
     * @return string an XHTML formatted snippet
     */
    public function getDiscountMessage(SiteApplication $app)
    {
        $message = SwatString::minimizeEntities(
            sprintf(
                $this->getDiscountMessageText($app),
                $this->getFormattedDiscount($app)
            )
        );

        $rules = $this->getPromotionRulesArray();
        if (count($rules) > 0) {
            $rules_span = new SwatHtmlTag('span');
            $rules_span->class = 'promo-promotion-rules';
            $rules_span->setContent(
                sprintf(
                    Promo::_('(* %s)'),
                    implode(', ', $rules)
                )
            );

            $message .= ' ' . $rules_span;
        }

        return $message;
    }

    public function getInactiveMessage(SiteApplication $app)
    {
        $start_date = $this->start_date;
        $end_date = $this->end_date;

        $now = new SwatDate();
        $now->toUTC();

        if ($start_date instanceof SwatDate && $now->before($start_date)) {
            $date = clone $start_date;
            $date->convertTZ($app->default_time_zone);

            $description = sprintf(
                Promo::_(
                    'The “%s” promotion is only available on or after %s.'
                ),
                $this->title,
                $date->formatLikeIntl(SwatDate::DF_DATE_LONG)
            );
        } elseif ($end_date instanceof SwatDate && $now->after($end_date)) {
            $date = clone $end_date;
            $date->convertTZ($app->default_time_zone);

            $description = sprintf(
                Promo::_('The “%s” promotion expired on %s.'),
                $this->title,
                $date->formatLikeIntl(SwatDate::DF_DATE_LONG)
            );
        } else {
            $description = sprintf(
                Promo::_('The “%s” promotion code has already been used.'),
                $this->getCode()
            );
        }

        return $description;
    }

    /**
     * Checks if this promotion is currently active.
     *
     * @param mixed $check_code
     *
     * @return bool true if this promotion is active and false if it is not
     */
    public function isActive($check_code = true)
    {
        $now = new SwatDate();
        $now->toUTC();

        $active = (
            !$this->start_date instanceof SwatDate
                || SwatDate::compare($now, $this->start_date) >= 0
        ) && (
            !$this->end_date instanceof SwatDate
            || SwatDate::compare($now, $this->end_date) <= 0
        );

        if ($active && $check_code) {
            $active = (
                $this->code instanceof PromoPromotionCode
                && (
                    !$this->code->limited_use
                    || !$this->code->used_date instanceof SwatDate
                )
            );
        }

        return $active;
    }

    public function loadByCode($code, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        $sql = sprintf(
            'select * from PromotionCode
			where lower(code) = lower(%s)',
            $this->db->quote($code, 'text')
        );

        $wrapper = SwatDBClassMap::get(PromoPromotionCodeWrapper::class);
        $promotion_code = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

        if ($promotion_code == '') {
            return false;
        }

        $this->code = $promotion_code;

        return $this->load(
            $promotion_code->getInternalValue('promotion'),
            $instance
        );
    }

    public function load($id, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        $loaded = false;
        $row = null;
        if ($this->table !== null && $this->id_field !== null) {
            $id_field = new SwatDBField($this->id_field, 'integer');

            $sql = sprintf(
                'select %1$s.* from %1$s
				where %1$s.%2$s = %3$s',
                $this->table,
                $id_field->name,
                $this->db->quote($id, $id_field->type)
            );

            if ($instance instanceof SiteInstance) {
                $sql .= sprintf(
                    ' and instance %s %s',
                    SwatDB::equalityOperator($instance->id),
                    $this->db->quote($instance->id, 'integer')
                );
            }

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $loaded = true;
        }

        return $loaded;
    }

    public function getCode()
    {
        $code = null;

        if ($this->code instanceof PromoPromotionCode) {
            $code = $this->code->code;
        }

        return $code;
    }

    public function getCodeCount()
    {
        $this->checkDB();

        $sql = sprintf(
            'select count(1) from PromotionCode where promotion = %s',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::queryOne($this->db, $sql);
    }

    public function getUsedCodeCount()
    {
        $this->checkDB();

        $sql = sprintf(
            'select count(1) from PromotionCode
			where promotion = %s and
				limited_use = true and
				used_date is not null',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::queryOne($this->db, $sql);
    }

    public function getUnusedCodeCount()
    {
        $this->checkDB();

        $sql = sprintf(
            'select count(1) from PromotionCode
			where promotion = %s and (
				limited_use = false or
				used_date is null
			)',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::queryOne($this->db, $sql);
    }

    public function setUsed()
    {
        if ($this->code instanceof PromoPromotionCode
            && $this->code->limited_use) {
            $this->code->used_date = new SwatDate();
            $this->code->setDatabase($this->db);
            $this->code->save();
        }
    }

    public function getValidDatesWithTz(
        DateTimeZone $time_zone,
        $date_format = SwatDate::DF_DATE_TIME,
        $tz_format = SwatDate::TZ_SHORT
    ) {
        return $this->getValidDates(
            $time_zone,
            $date_format,
            $tz_format
        );
    }

    public function getValidDates(
        DateTimeZone $time_zone,
        $date_format = SwatDate::DF_DATE_TIME,
        $tz_format = null
    ) {
        $valid_dates = Promo::_('Always Active');

        if ($this->start_date instanceof SwatDate
            || $this->end_date instanceof SwatDate) {
            if ($this->start_date instanceof SwatDate) {
                $start_date = clone $this->start_date;
                $start_date->convertTZ($time_zone);
                $start_date = $start_date->formatLikeIntl(
                    $date_format,
                    $tz_format
                );
            }

            if ($this->end_date instanceof SwatDate) {
                $end_date = clone $this->end_date;
                $end_date->convertTZ($time_zone);
                $end_date = $end_date->formatLikeIntl(
                    $date_format,
                    $tz_format
                );
            }

            if ($this->start_date instanceof SwatDate
                && $this->end_date instanceof SwatDate) {
                $valid_dates = sprintf(
                    Promo::_('%s to %s'),
                    $start_date,
                    $end_date
                );
            } elseif ($this->start_date instanceof SwatDate) {
                $valid_dates = sprintf(
                    Promo::_('From %s'),
                    $start_date
                );
            } elseif ($this->end_date instanceof SwatDate) {
                $valid_dates = sprintf(
                    Promo::_('Until %s'),
                    $end_date
                );
            }
        }

        return $valid_dates;
    }

    /**
     * Gets a text description of this promotion's discount.
     *
     * This is a helper method used by the public
     * {@link PromoPromotion::getDiscountMessage()} method.
     *
     * @return string
     */
    protected function getDiscountMessageText(SiteApplication $app)
    {
        if ($this->isFixedDiscount()) {
            $message = Promo::_('up to %s off your order');
        } else {
            $message = Promo::_('%s off your order');
        }

        return $message;
    }

    protected function getPromotionRulesArray()
    {
        $rules = [];

        if ($this->maximum_quantity !== null) {
            $locale = SwatI18NLocale::get();

            $rules[] = sprintf(
                Promo::ngettext(
                    'Limited to one item per order',
                    'Limited to %s items per order',
                    $this->maximum_quantity
                ),
                $locale->formatNumber($this->maximum_quantity)
            );
        }

        return $rules;
    }

    protected function init()
    {
        parent::init();

        $this->table = 'Promotion';
        $this->id_field = 'integer:id';

        $this->registerDateProperty('start_date');
        $this->registerDateProperty('end_date');

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );
    }

    protected function getSerializablePrivateProperties()
    {
        $array = parent::getSerializablePrivateProperties();
        $array[] = 'code';

        return $array;
    }

    // loader methods

    protected function loadCodes()
    {
        $sql = 'select * from PromotionCode
			where promotion = %s';

        $sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(PromoPromotionCodeWrapper::class)
        );
    }

    // saver methods

    protected function saveCodes()
    {
        foreach ($this->codes as $code) {
            $code->promotion = $this;
        }

        $this->codes->setDatabase($this->db);
        $this->codes->save();
    }
}
