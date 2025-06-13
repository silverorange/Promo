<?php

/**
 * Page to generate a set of promotion codes.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionGenerateCodes extends AdminDBEdit
{
    /**
     * @var PromoPromotion
     */
    protected $promotion;

    protected function getUiXml()
    {
        return __DIR__ . '/generate-codes.xml';
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getUiXml());

        $this->initPromotion();
    }

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
                    $promotion_id
                )
            );
        }
    }

    // process phase

    protected function saveDBData(): void
    {
        $values = $this->ui->getValues(
            [
                'prefix',
                'quantity',
                'limited_use',
            ]
        );

        $prefix = mb_strtolower($values['prefix']);

        $now = new SwatDate();
        $now->toUTC();

        $values_string = sprintf(
            '(%s, %s, %s, %%s)',
            $this->app->db->quote($this->promotion->id, 'integer'),
            $this->app->db->quote($values['limited_use'], 'boolean'),
            $this->app->db->quote($now, 'date')
        );

        $generator = $this->getPromotionCodeGenerator();
        $codes = $generator->getCodes(
            $this->promotion,
            $values['quantity'],
            $values['prefix']
        );

        // insert values
        $values_out = [];
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
            implode(',', $values_out)
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

    protected function getPromotionCodeGenerator()
    {
        return new PromoPromotionCodeGenerator($this->app);
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $this->ui->getWidget('edit_frame')->title =
            Promo::_('Generate Promotion Codes');
    }

    protected function buildForm()
    {
        parent::buildForm();

        $this->ui->getWidget('edit_form')->addHiddenField(
            'promotion',
            $this->promotion->id
        );
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

        $this->navbar->createEntry(
            Promo::_('Generate Promotion Codes')
        );
    }

    protected function loadDBData()
    {
        // do nothing;
    }
}
