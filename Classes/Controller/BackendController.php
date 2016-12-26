<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Controller;


use T3G\Hubspot\Service\UsedFormsService;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var ButtonBar
     */
    protected $buttonBar;

    /**
     * @var MenuRegistry
     */
    protected $menuRegistry;

    public function initializeAction()
    {
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->pageRenderer = $this->moduleTemplate->getPageRenderer();
        $this->pageRenderer->addCssFile(
            ExtensionManagementUtility::extRelPath('hubspot') . 'Resources/Public/Css/backend.css'
        );
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $this->menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $this->createMenu();
    }

    public function indexAction()
    {
        return $this->renderAction(
            'Index.html',
            [
                'formsView' => $this->uriBuilder->reset()->uriFor(
                    'forms',
                    [],
                    'Backend'
                )
            ]
        );
    }

    public function formsAction()
    {
        $usedFormsService = GeneralUtility::makeInstance(UsedFormsService::class);
        $formsInUse = $usedFormsService->getFormsInUseWithDetails();
        return $this->renderAction(
            'Forms.html',
            [
                'formsInUse' => $formsInUse
            ]
        );
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
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function createMenuItem(Menu $menu, string $action, string $title)
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

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $template
     *
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    protected function getFluidTemplateObject($template)
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(
            [GeneralUtility::getFileAbsFileName('EXT:hubspot/Resources/Private/Backend/Layouts')]
        );
        $view->setPartialRootPaths(
            [GeneralUtility::getFileAbsFileName('EXT:hubspot/Resources/Private/Backend/Partials')]
        );
        $view->setTemplateRootPaths(
            [GeneralUtility::getFileAbsFileName('EXT:hubspot/Resources/Private/Backend/Templates')]
        );
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:hubspot/Resources/Private/Backend/Templates/' . $template)
        );
        $view->setControllerContext($this->getControllerContext());
        $view->getRequest()->setControllerExtensionName('hubspot');
        return $view;
    }

    /**
     * @param string $template
     * @param array $values
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    private function renderAction(string $template, array $values): string
    {
        $view = $this->getFluidTemplateObject($template);
        $view->assignMultiple($values);
        $this->moduleTemplate->setContent($view->render());
        return $this->moduleTemplate->renderContent();
    }


}
