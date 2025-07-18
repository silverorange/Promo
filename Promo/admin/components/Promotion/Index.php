<?php

/**
 * Index page for Promotions.
 *
 * @copyright 2011-2022 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionIndex extends AdminSearch
{
    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    protected function getSearchXml()
    {
        return __DIR__ . '/search.xml';
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getSearchXml());
        $this->ui->loadFromXML($this->getUiXml());

        $this->ui->getWidget('search_status')->value = 'active';
        $this->ui->getWidget('search_status')->addOptionsByArray([
            'active'   => Promo::_('Active'),
            'inactive' => Promo::_('Inactive'),
        ]);
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Promotion/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $view = $this->ui->getWidget('index_view');

        if ($view->hasGroup('instance_group')) {
            $view->getGroup('instance_group')->visible =
                $this->app->isMultipleInstanceAdmin();
        }
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            $this->getSQL(),
            $this->getWhereClause(),
            $this->getOrderByClause(
                $view,
                'instance_title nulls first, Promotion.instance nulls first, ' .
                'title'
            )
        );

        $promotions = SwatDB::query($this->app->db, $sql);

        $store = new SwatTableStore();
        foreach ($promotions as $row) {
            $promotion = SwatDBClassMap::new(PromoPromotion::class, $row);
            $promotion->setDatabase($this->app->db);

            $ds = $this->getPromotionDetailsStore(
                $promotion,
                $row
            );

            $store->add($ds);
        }

        return $store;
    }

    protected function getSQL()
    {
        // Need to coalesce here to handle promotions with no codes or no
        // orders that are not reflected in the PromotionROI view.
        return 'select Promotion.*,
				coalesce(PromotionROIView.num_orders, 0) as num_orders,
				PromotionROIView.promotion_total, PromotionROIView.total,
				Instance.title as instance_title
			from Promotion
			left outer join Instance on Promotion.instance = Instance.id
			left outer join PromotionROIView on
				Promotion.id = PromotionROIView.promotion
			where %s
			order by %s';
    }

    protected function getWhereClause()
    {
        $where = '1 = 1';

        $instance = $this->app->getInstance();
        if ($instance instanceof SiteInstance) {
            $where .= sprintf(
                ' and Promotion.instance = %s',
                $this->app->db->quote($instance->id, 'integer')
            );
        }

        $now = new SwatDate();
        $now->toUTC();

        $status = $this->ui->getWidget('search_status')->value;
        if ($status === 'active') {
            $where .= sprintf(
                ' and (Promotion.end_date is null or Promotion.end_date >= %s)',
                $this->app->db->quote($now->getDate(), 'date')
            );
        } else {
            $where .= sprintf(
                ' and Promotion.end_date < %s',
                $this->app->db->quote($now->getDate(), 'date')
            );
        }

        return $where;
    }

    protected function getPromotionDetailsStore(
        PromoPromotion $promotion,
        $row
    ) {
        $ds = new SwatDetailsStore($promotion);
        $ds->show_discount_amount = $promotion->isFixedDiscount();
        $ds->valid_dates = $promotion->getValidDates(
            $this->app->default_time_zone,
            SwatDate::DF_DATE_TIME_SHORT
        );

        $ds->is_active = $promotion->isActive(false);

        $ds->num_orders = ($row->num_orders === null)
            ? 0
            : $row->num_orders;

        $ds->promotion_total = $row->promotion_total;
        $ds->total = $row->total;

        if ($ds->promotion_total === null) {
            $ds->roi_infinite = false;
            $ds->roi = null;
        } elseif ($ds->promotion_total == 0) {
            $ds->roi_infinite = true;
            $ds->roi = 0;
        } else {
            $ds->roi_infinite = false;
            $ds->roi = ($ds->total - $ds->promotion_total) /
                $ds->promotion_total;
        }

        $ds->has_notes = ($promotion->notes != '');
        $ds->notes = SwatString::minimizeEntities($ds->notes);
        $ds->notes = '<span class="admin-notes">' . $ds->notes . '</span>';

        return $ds;
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry(
            'packages/promo/admin/styles/promo-promotion-index.css'
        );
    }
}
