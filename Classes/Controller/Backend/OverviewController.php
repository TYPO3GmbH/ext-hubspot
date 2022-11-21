<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;


class OverviewController extends AbstractController
{
    /**
     * Render overview of available hubspot integration backend modules
     */
    public function indexAction()
    {
        $this->view->assignMultiple(
            [
                'formsView' => $this->uriBuilder->reset()->uriFor(
                    'index',
                    [],
                    'Backend\\Form'
                ),
                'ctasView' => $this->uriBuilder->reset()->uriFor(
                    'index',
                    [],
                    'Backend\\Cta'
                ),
                'customObjectsView' => $this->uriBuilder->reset()->uriFor(
                    'index',
                    [],
                    'Backend\\Schema'
                ),
            ]
        );
    }
}
