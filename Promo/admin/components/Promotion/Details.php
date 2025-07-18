<?php

/**
 * Details page for a promotion.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionDetails extends AdminIndex
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var PromoPromotion
     */
    protected $promotion;

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getUiXml());

        $this->initPromotion();
        $this->checkInstance();
    }

    protected function initPromotion()
    {
        $this->id = SiteApplication::initVar('id');

        $this->promotion = SwatDBClassMap::new(PromoPromotion::class);
        $this->promotion->setDatabase($this->app->db);

        if (!$this->promotion->load($this->id)) {
            throw new AdminNotFoundException(
                sprintf(
                    'A promotion with an id of ‘%d’ does not exist.',
                    $this->id
                )
            );
        }
    }

    protected function checkInstance()
    {
        $instance = $this->app->getInstance();
        if (
            $instance instanceof SiteInstance
            && !(
                $this->promotion->instance instanceof SiteInstance
                && $this->promotion->instance->id === $instance->id
            )
        ) {
            throw new AdminNotFoundException(
                sprintf(
                    'Incorrect instance for promotion ‘%s’.',
                    $this->id
                )
            );
        }
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'promotion_code_delete':
                $this->app->replacePage('Promotion/DeletePromotionCode');
                $this->app->getPage()->setItems($view->checked_items);
                break;
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $this->buildToolbars();

        $this->title = $this->promotion->title;

        $frame = $this->ui->getWidget('details_frame');
        $frame->subtitle = $this->title;

        $view = $this->ui->getWidget('details_view');
        $view->data = $this->getDetailsStore();

        if ($this->promotion->start_date instanceof SwatDate
            || $this->promotion->end_date instanceof SwatDate) {
            $field = $view->getField('active_period_field');
            $field->getRenderer('active_period_note_renderer')->visible = true;
        }

        if ($this->app->isMultipleInstanceAdmin()) {
            $instance_field = $view->getField('instance_field');
            $instance_field->visible = true;
        }

        // set the timezone on the used date column;
        $used_column =
            $this->ui->getWidget('promotion_code_view')->getColumn('used_date');

        $used_renderer = $used_column->getRendererByPosition();
        $used_renderer->display_time_zone = $this->app->default_time_zone;

        $this->buildPromotionCodeText();

        if ($this->promotion->getUnusedCodeCount() === 0) {
            $message = new SwatMessage(
                Promo::_('Wait! This promotion can’t be used!'),
                'warning'
            );

            $message->secondary_content = sprintf(
                Promo::_(
                    'This promotion doesn’t have any available promotion ' .
                    'codes. %sAdd%s or %sgenerate%s promotion codes so this ' .
                    'promotion can be entered by customers.'
                ),
                sprintf(
                    '<a href="Promotion/PromotionCodeEdit?promotion=%s">',
                    $this->id
                ),
                '</a>',
                sprintf(
                    '<a href="Promotion/GenerateCodes?promotion=%s">',
                    $this->id
                ),
                '</a>'
            );

            $message->content_type = 'text/xml';

            $this->ui->getWidget('warning_message_display')->add(
                $message,
                SwatMessageDisplay::DISMISS_OFF
            );
        }
    }

    protected function buildToolbars()
    {
        foreach ($this->ui->getRoot()->getDescendants('SwatToolbar') as $toolbar) {
            $toolbar->setToolLinkValues($this->promotion->id);
        }
    }

    protected function getDetailsStore()
    {
        $ds = new SwatDetailsStore($this->promotion);

        $ds->show_discount_amount = ($this->promotion->discount_amount > 0);

        $ds->valid_dates = $this->promotion->getValidDatesWithTz(
            $this->app->default_time_zone
        );

        $ds->note_edit_link = sprintf(
            'Promotion/NoteEdit?id=%s',
            $this->promotion->id
        );

        $ds->order_summary = $this->getOrderSummary();
        $ds->has_orders = ($ds->order_summary != '');

        return $ds;
    }

    protected function getOrderSummary()
    {
        $sql = sprintf(
            'select count(1) as num_orders,
				sum(promotion_total) as promotion_total, sum(total) as total
			from Orders
			where cancel_date is null and promotion_code in (
				select code from PromotionCode where promotion = %s
			)',
            $this->app->db->quote($this->promotion->id, 'integer')
        );

        $row = SwatDB::queryRow(
            $this->app->db,
            $sql,
            ['integer', 'float', 'float']
        );

        $locale = SwatI18NLocale::get();

        if ($row->num_orders === 0) {
            $summary = null;
        } else {
            if ($row->promotion_total == 0) {
                $formatted_roi = Promo::_('∞');
            } else {
                $formatted_roi = sprintf(
                    Promo::_('%s%%'),
                    $locale->formatNumber(
                        SwatNumber::roundToEven(
                            (
                                ($row->total - $row->promotion_total) /
                                $row->promotion_total
                            ) * 100,
                            2
                        ),
                        2
                    )
                );
            }

            $summary = sprintf(
                Promo::ngettext(
                    '%s order, %s return on investment',
                    '%s orders, %s return on investment',
                    $row->num_orders
                ),
                $locale->formatNumber($row->num_orders),
                $formatted_roi
            );
        }

        return $summary;
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        switch ($view->id) {
            case 'promotion_code_view':
                return $this->getPromotionCodeTableModel($view);
        }

        return null;
    }

    protected function getPromotionCodeTableModel(SwatTableView $view): PromoPromotionCodeWrapper
    {
        $sql = sprintf(
            'select count(id) from PromotionCode
			where promotion = %s',
            $this->app->db->quote($this->promotion->id, 'integer')
        );

        $pager = $this->ui->getWidget('promotion_code_pager');
        $pager->total_records = SwatDB::queryOne($this->app->db, $sql);

        $sql = sprintf(
            'select * from PromotionCode
			where promotion = %s
			order by %s',
            $this->app->db->quote($this->promotion->id, 'integer'),
            $this->getOrderByClause($view, 'code')
        );

        $this->app->db->setLimit($pager->page_size, $pager->current_record);

        $promotion_codes = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(PromoPromotionCodeWrapper::class)
        );

        if (count($promotion_codes) > 0) {
            $this->ui->getWidget('results_message')->content_type = 'txt/xml';
            $this->ui->getWidget('results_message')->content = sprintf(
                '<p id="results_message">%s</p>',
                $pager->getResultsMessage(
                    Promo::_('promotion code'),
                    Promo::_('promotion codes')
                )
            );
        }

        return $promotion_codes;
    }

    protected function buildPromotionCodeText()
    {
        $sql = sprintf(
            'select * from PromotionCode
			where promotion = %s and (
				used_date is null or limited_use = false
			)
			order by limited_use, code',
            $this->app->db->quote($this->promotion->id, 'integer')
        );

        $codes = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(PromoPromotionCodeWrapper::class)
        );

        if (count($codes) > 0) {
            $unlimited_code_text = [];
            $limited_code_text = [];
            foreach ($codes as $code) {
                if ($code->limited_use) {
                    $limited_code_text[] = $code->code;
                } else {
                    $unlimited_code_text[] = $code->code;
                }
            }

            $unlimited_code_text = implode(', ', $unlimited_code_text);
            $limited_code_text = implode(', ', $limited_code_text);

            if ($unlimited_code_text != '' && $limited_code_text != '') {
                $code_text = sprintf(
                    Promo::_("Unlimited Use:\n%s\n\nOne-Time Use:\n%s"),
                    $unlimited_code_text,
                    $limited_code_text
                );
            } elseif ($unlimited_code_text != '') {
                $code_text = sprintf(
                    Promo::_("Unlimited Use:\n%s"),
                    $unlimited_code_text
                );
            } elseif ($limited_code_text != '') {
                $code_text = sprintf(
                    Promo::_("One-Time Use:\n%s"),
                    $limited_code_text
                );
            } else {
                $code_text = Promo::_('None');
            }

            $this->ui->getWidget('promotion_code_summary')->visible = true;
            $summary = $this->ui->getWidget('promotion_code_summary_text');
            $summary->content = sprintf(
                '<p>%s</p>',
                nl2br(SwatString::minimizeEntities($code_text))
            );
            $summary->content_type = 'text/xml';
        }
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->navbar->createEntry($this->promotion->title);
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry(
            'packages/promo/admin/styles/promo-admin-notices.css'
        );

        $this->layout->addHtmlHeadEntry(
            'packages/promo/admin/styles/promo-promotion-details.css'
        );
    }
}
