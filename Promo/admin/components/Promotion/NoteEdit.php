<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoPromotionNoteEdit extends AdminObjectEdit
{
    protected function getObjectClass()
    {
        return 'PromoPromotion';
    }

    protected function getUiXml()
    {
        return __DIR__ . '/note-edit.xml';
    }

    protected function getObjectUiValueNames()
    {
        return [
            'notes',
        ];
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->checkPromotion();
        $this->checkInstance();
    }

    protected function checkPromotion()
    {
        if ($this->isNew()) {
            throw new AdminNotFoundException(
                'Promotion note editing requires an existing promotion.'
            );
        }
    }

    protected function checkInstance()
    {
        $instance = $this->app->getInstance();
        $promotion = $this->getObject();

        if (
            $instance instanceof SiteInstance
            && !(
                $promotion->instance instanceof SiteInstance
                && $promotion->instance->id === $instance->id
            )
        ) {
            throw new AdminNotFoundException(
                sprintf(
                    'Incorrect instance for promotion ‘%s’.',
                    $promotion->id
                )
            );
        }
    }

    // process phase

    protected function getSavedMessagePrimaryContent()
    {
        return Promo::_('Note has been saved.');
    }

    // build phase

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $promotion = $this->getObject();
        $this->navbar->popEntry();
        $this->navbar->createEntry(
            $promotion->title,
            sprintf(
                'Promotion/Details?id=%s',
                $promotion->id
            )
        );

        $this->navbar->createEntry(
            Promo::_('Edit Note')
        );
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry(
            'packages/promo/admin/styles/promo-admin-notices.css'
        );
    }
}
