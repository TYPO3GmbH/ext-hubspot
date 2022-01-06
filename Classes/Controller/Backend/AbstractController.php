<?php

declare(strict_types=1);

namespace T3G\Hubspot\Controller\Backend;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Abstract backend controller
 */
abstract class AbstractController extends ActionController
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize view and add Css
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        $this->moduleTemplate = $view->getModuleTemplate();

        // The current action has no template defined.
        if ($this->moduleTemplate === null) {
            return;
        }

        $this->moduleTemplate->getPageRenderer()->addCssFile('EXT:hubspot/Resources/Public/Css/backend.css');

        $this->populateDocHeader();
    }

    /**
     * Initialize actions
     */
    public function initializeAction()
    {
        $this->getLanguageService()->includeLLFile('EXT:hubspot/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Method for populating items in the DocHeader.
     */
    protected function populateDocHeader(): void
    {
        $this->createMenu();
    }

    /**
     * Create and populate the DocHeader menu.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function createMenu()
    {
        $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();

        $menu = $menuRegistry->makeMenu();

        $menu->setIdentifier('hubspot_module_menu');

        $menu->addMenuItem(
            $menu
                ->makeMenuItem()
                ->setHref($this->uriBuilder->uriFor('index', null, 'Backend\\Overview'))
                ->setTitle($this->getLanguageService()->getLL('hubspot_integration.mainMenu.index'))
                ->setActive($this->request->getControllerObjectName() === OverviewController::class)
        );

        $menu->addMenuItem(
            $menu
                ->makeMenuItem()
                ->setHref($this->uriBuilder->uriFor('index', null, 'Backend\\Form'))
                ->setTitle($this->getLanguageService()->getLL('hubspot_integration.mainMenu.forms'))
                ->setActive($this->request->getControllerObjectName() === FormController::class)
        );

        $menu->addMenuItem(
            $menu
                ->makeMenuItem()
                ->setHref($this->uriBuilder->uriFor('index', null, 'Backend\\Cta'))
                ->setTitle($this->getLanguageService()->getLL('hubspot_integration.mainMenu.ctas'))
                ->setActive($this->request->getControllerObjectName() === CtaController::class)
        );

        $menu->addMenuItem(
            $menu
                ->makeMenuItem()
                ->setHref($this->uriBuilder->uriFor('index', null, 'Backend\\Schema'))
                ->setTitle($this->getLanguageService()->getLL('hubspot_integration.mainMenu.customObjects'))
                ->setActive($this->request->getControllerObjectName() === SchemaController::class)
        );

        $menuRegistry->addMenu($menu);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
