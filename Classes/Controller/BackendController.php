<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Controller;


use T3G\Hubspot\Service\UsedFormsService;
use T3G\Hubspot\View\BackendTemplateView;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Backend module for Hubspot Integration
 *
 * @package T3G\Hubspot\Controller
 */
class BackendController extends ActionController
{

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var MenuRegistry
     */
    protected $menuRegistry;

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
        $this->pageRenderer = $this->moduleTemplate->getPageRenderer();
        $this->pageRenderer->addCssFile('EXT:hubspot/Resources/Public/Css/backend.css');
        $this->menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $this->createMenu();
    }

    /**
     * Initialize actions
     */
    public function initializeAction()
    {
        $this->setBackendModuleTemplates();
    }

    /**
     * Render overview of available hubspot integration backend modules
     */
    public function indexAction()
    {
        $this->view->assign(
            'formsView', $this->uriBuilder->reset()->uriFor('forms', [], 'Backend')
        );
    }

    /**
     * Render all forms in use
     */
    public function formsAction()
    {
        $usedFormsService = GeneralUtility::makeInstance(UsedFormsService::class);
        $formsInUse = $usedFormsService->getFormsInUseWithDetails();
        $this->view
            ->assign(
                'formsInUse', $formsInUse
            )
            ->assign(
                'returnUrl', urlencode($this->uriBuilder->reset()->uriFor('forms', [], 'Backend'))
            );
    }

    /**
     * Renders Iframe with hubspot form in backend module
     *
     * @param string $hubspotGuid
     */
    public function hubspotFormAction(string $hubspotGuid)
    {
        // overwrite menu including this action as active
        $menu = $this->menuRegistry->makeMenu();
        $menu->setIdentifier('hubspot_module_menu');
        $menu = $this->createMenuItem($menu, 'index', 'Overview');
        $menu = $this->createMenuItem($menu, 'forms', 'Forms');
        $menuItem = $menu->makeMenuItem();
        $menuItem
            ->setTitle('Hubspot Form')
            ->setHref('#')
            ->setActive(true);
        $menu->addMenuItem($menuItem);
        $this->menuRegistry->addMenu($menu);

        $this->view
            ->assign('portalId', (int)$_ENV['HUBSPOT_PORTALID'])
            ->assign('hubspotGuid', $hubspotGuid);
    }

    /**
     * Set Backend Module Templates
     *
     * @return void
     */
    private function setBackendModuleTemplates()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $viewConfiguration = [
            'view' => [
                'templateRootPaths' => ['EXT:hubspot/Resources/Private/Backend/Templates'],
                'partialRootPaths' => ['EXT:hubspot/Resources/Private/Backend/Partials'],
                'layoutRootPaths' => ['EXT:hubspot/Resources/Private/Backend/Layouts'],
            ],
        ];
        $this->configurationManager->setConfiguration(array_merge($frameworkConfiguration, $viewConfiguration));
    }

    /**
     * create backend toolbar menu
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function createMenu()
    {
        $menu = $this->menuRegistry->makeMenu();
        $menu->setIdentifier('hubspot_module_menu');

        $menu = $this->createMenuItem($menu, 'index', 'Overview');
        $menu = $this->createMenuItem($menu, 'forms', 'Forms');

        $this->menuRegistry->addMenu($menu);
    }

    /**
     * @param \TYPO3\CMS\Backend\Template\Components\Menu\Menu $menu
     * @param string $action
     * @param string $title
     * @return Menu
     * @throws \InvalidArgumentException
     */
    private function createMenuItem(Menu $menu, string $action, string $title) : Menu
    {
        $menuItem = $menu->makeMenuItem();
        $isActive = $this->request->getControllerActionName() === $action;
        $uri = $this->uriBuilder->reset()->uriFor($action, [], 'Backend');
        $menuItem
            ->setTitle($title)
            ->setHref($uri)
            ->setActive($isActive);
        $menu->addMenuItem($menuItem);
        return $menu;
    }

}
