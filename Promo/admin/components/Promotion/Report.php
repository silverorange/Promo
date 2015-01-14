<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNumber.php';
require_once 'Swat/SwatNumericCellRenderer.php';
require_once 'Swat/SwatPercentageCellRenderer.php';
require_once 'Swat/SwatMoneyCellRenderer.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * Displays sales summaries for a promotion by month
 *
 * @package   Promo
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionReport extends AdminIndex
{
	// {{{ protected properties

	/**
	 * Cache of regions used by getRegions()
	 *
	 * @var StoreRegionWrapper
	 */
	protected $regions = null;

	/**
	 * @var PromoPromotion
	 */
	protected $promotion;

	/**
	 * @var SwatDate
	 */
	protected $start_date;

	/**
	 * @var SwatDate
	 */
	protected $end_date;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/report.xml';
	}

	// }}}
	// {{{ protected function getRegions()

	protected function getRegions()
	{
		if (!$this->regions instanceof StoreRegionWrapper) {
			$sql = 'select Region.id, Region.title
				from Region
				order by Region.id';

			$this->regions = SwatDB::query(
				$this->app->db,
				$sql,
				SwatDBClassMap::get('StoreRegionWrapper')
			);
		}

		return $this->regions;
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$this->initPromotion();
		$this->checkInstance();

		$regions = $this->getRegions();
		$view = $this->ui->getWidget('index_view');

		// add dynamic columns to items view
		$this->appendRegionColumns($view, $regions);

		$row = SwatDB::queryRow(
			$this->app->db,
			sprintf(
				'select
						min(createdate) as start_date,
						max(createdate) as end_date
					from Orders
				where promotion_code in
					(select code from PromotionCode where promotion = %s)',
				$this->app->db->quote($this->promotion->id, 'integer')
			)
		);

		$this->start_date = new SwatDate($row->start_date);
		$this->start_date->setTimezone($this->app->default_time_zone);
		$this->start_date->setDay(1);
		$this->start_date->setTime(0, 0, 0);

		$this->end_date = new SwatDate($row->end_date);
		$this->end_date->setTimezone($this->app->default_time_zone);
		$this->end_date->setDay(1);
	}

	// }}}
	// {{{ protected function initPromotion()

	protected function initPromotion()
	{
		$this->id = SiteApplication::initVar('id');
		$promotion_class = SwatDBClassMap::get('PromoPromotion');

		$this->promotion = new $promotion_class();
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

	// }}}
	// {{{ protected function checkInstance()

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
					$this->id
				)
			);
		}
	}

	// }}}
	// {{{ protected function appendRegionColumns()

	protected function appendRegionColumns(SwatTableView $view,
		StoreRegionWrapper $regions)
	{
		foreach ($regions as $region) {
			$created_column = new SwatTableViewColumn('created_'.$region->id);

			if (count($regions) === 1) {
				$created_column->title = Promo::_('Orders');
			} else {
				$created_column->title = sprintf(
					Promo::_('%s Orders'),
					$region->title
				);
			}

			$created_renderer = new SwatNumericCellRenderer();

			$created_column->addRenderer($created_renderer);
			$created_column->addMappingToRenderer(
				$created_renderer,
				'created_'.$region->id,
				'value'
			);

			// promotion total
			$promotion_total_column = new SwatTableViewColumn(
				'promotion_total_'.$region->id
			);

			if (count($regions) === 1) {
				$promotion_total_column->title = Promo::_('Promotion Cost');
			} else {
				$promotion_total_column->title = sprintf(
					Promo::_('%s Promotion Cost'),
					$region->title
				);
			}

			$promotion_total_renderer = new SwatMoneyCellRenderer();
			$promotion_total_renderer->locale = $region->getFirstLocale()->id;

			$promotion_total_column->addRenderer($promotion_total_renderer);
			$promotion_total_column->addMappingToRenderer(
				$promotion_total_renderer,
				'promotion_total_'.$region->id,
				'value'
			);

			$promotion_total_column->addMappingToRenderer(
				$promotion_total_renderer,
				'locale_id',
				'locale'
			);

			// order total
			$total_column = new SwatTableViewColumn(
				'total_'.$region->id
			);

			if (count($regions) === 1) {
				$total_column->title = Promo::_('Total');
			} else {
				$total_column->title = sprintf(
					Promo::_('%s Total'),
					$region->title
				);
			}

			$total_renderer = new SwatMoneyCellRenderer();
			$total_renderer->locale = $region->getFirstLocale()->id;

			$total_column->addRenderer($total_renderer);
			$total_column->addMappingToRenderer(
				$total_renderer,
				'total_'.$region->id,
				'value'
			);

			$total_column->addMappingToRenderer(
				$total_renderer,
				'locale_id',
				'locale'
			);

			// return on investment
			$roi_column = new SwatTableViewColumn('roi_'.$region->id);

			if (count($regions) === 1) {
				$roi_column->title = Promo::_('Return on Investment');
			} else {
				$roi_column->title = sprintf(
					Promo::_('%s Return on Investment'),
					$region->title
				);
			}

			$roi_renderer = new SwatPercentageCellRenderer();
			$roi_renderer->precision = 2;
			$roi_renderer->locale = $region->getFirstLocale()->id;

			$roi_infinite_renderer = new SwatTextCellRenderer();
			$roi_infinite_renderer->text = Promo::_('∞');

			$roi_column->addRenderer($roi_renderer);
			$roi_column->addMappingToRenderer(
				$roi_renderer,
				'roi_'.$region->id,
				'value'
			);
			$roi_column->addMappingToRenderer(
				$roi_renderer,
				'!roi_infinite_'.$region->id,
				'visible'
			);

			$roi_column->addRenderer($roi_infinite_renderer);
			$roi_column->addMappingToRenderer(
				$roi_infinite_renderer,
				'roi_infinite_'.$region->id,
				'visible'
			);

			$view->appendColumn($created_column);
			$view->appendColumn($promotion_total_column);
			$view->appendColumn($total_column);
			$view->appendColumn($roi_column);
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$date = clone $this->start_date;

		$regions = $this->getRegions();
		$locale_id = $regions->getFirst()->getFirstLocale()->id;

		// create an array of months with default values
		$months = array();

		do {
			$key = $date->format('Y-n');

			$month = new SwatDetailsStore();

			foreach ($regions as $region) {
				$month->{'created_'.$region->id}         = 0;
				$month->{'promotion_total_'.$region->id} = 0;
				$month->{'total_'.$region->id}           = 0;
				$month->{'roi_'.$region->id}             = 0;
				$month->{'roi_infinite_'.$region->id}    = 0;
			}

			$month->date      = clone $date;
			$month->locale_id = $locale_id;

			$months[$key] = $month;

			$date->addMonths(1);
		} while ($date->before($this->end_date));

		// totl row
		$total = new SwatDetailsStore();

		$total->date      = null;
		$total->locale_id = $locale_id;
		foreach ($regions as $region) {
			$total->{'created_'.$region->id}         = 0;
			$total->{'promotion_total_'.$region->id} = 0;
			$total->{'total_'.$region->id}           = 0;
			$total->{'roi_'.$region->id}             = 0;
			$total->{'roi_infinite_'.$region->id}    = false;
		}

		$months['total'] = $total;

		// fill our array with values from the database if the values exist
		$rs = $this->queryOrderStats();
		foreach ($rs as $row) {
			$key = $row->year.'-'.$row->month;

			$months[$key]->{'created_'.$row->region} = $row->num_orders;
			$months[$key]->{'total_'.$row->region} = $row->total;
			$months[$key]->{'promotion_total_'.$row->region} =
				$row->promotion_total;

			if ($row->promotion_total == 0) {
				$months[$key]->{'roi_infinite_'.$row->region} = true;
			} else {
				$months[$key]->{'roi_'.$row->region} =
					($row->total - $row->promotion_total) /
					$row->promotion_total;
			}

			$total->{'created_'.$region->id}         += $row->num_orders;
			$total->{'promotion_total_'.$region->id} += $row->promotion_total;
			$total->{'total_'.$region->id}           += $row->total;
		}

		// calculate total ROI per region
		foreach ($regions as $region) {
			if ($total->{'promotion_total_'.$region->id} == 0) {
				$total->{'roi_infinite_'.$region->id} = true;
			} else {
				$total->{'roi_'.$region->id} =
					($total->{'total_'.$region->id} -
					$total->{'promotion_total_'.$region->id}) /
					$total->{'promotion_total_'.$region->id};
			}
		}

		// turn the array into a table model
		$store = new SwatTableStore();
		foreach ($months as $month) {
			$store->add($month);
		}

		return $store;
	}

	// }}}
	// {{{ protected function queryOrderStats()

	protected function queryOrderStats()
	{
		$where_clause = '1 = 1';

		$instance = $this->app->getInstance();
		if ($instance instanceof SiteInstance) {
			$where_clause.= sprintf(
				' and Orders.instance %s %s',
				SwatDB::equalityOperator($instance->id),
				$this->app->db->quote($instance->id, 'integer')
			);
		}

		$sql = 'select count(Orders.id) as num_orders, Locale.region,
				sum(promotion_total) as promotion_total, sum(total) as total,
				extract(month from convertTZ(createdate, %1$s)) as month,
				extract(year from convertTZ(createdate, %1$s)) as year
			from Orders
				inner join Locale on Orders.locale = Locale.id
			where
				%2$s and
				promotion_code in
					(select code from PromotionCode where promotion = %3$s)
			group by Locale.region, year, month
			order by year, month';

		$sql = sprintf(
			$sql,
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$where_clause,
			$this->app->db->quote($this->promotion->id, 'integer')
		);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->createEntry(
			$this->promotion->title,
			sprintf(
				'Promotion/Details?id=%s',
				$this->promotion->id
			)
		);

		$this->navbar->createEntry(
			Promo::_('Promotion Use Details')
		);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-promotion-report.css'
		);
	}

	// }}}
}

?>
