<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use T3G\Hubspot\Repository\HubspotCustomObjectSchemaRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for inspecting Hubspot custom object schemas.
 */
class SchemaController extends AbstractController
{
    /**
     * @var HubspotCustomObjectSchemaRepository
     */
    protected $schemaRepository;

    /**
     * @param HubspotCustomObjectSchemaRepository|null $schemaRepository
     */
    public function __construct(HubspotCustomObjectSchemaRepository $schemaRepository = null)
    {
        $this->schemaRepository = $schemaRepository
            ?? GeneralUtility::makeInstance(HubspotCustomObjectSchemaRepository::class);
    }

    /**
     * Method for populating items in the DocHeader.
     */
    protected function populateDocHeader(): void
    {
        parent::populateDocHeader();

        $iconFactory = $this->moduleTemplate->getIconFactory();

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        if ($this->request->getControllerActionName() !== 'index') {
            $buttonBar->addButton(
                $buttonBar
                    ->makeLinkButton()
                    ->setHref($this->controllerContext->getUriBuilder()->uriFor('index'))
                    ->setTitle($this->getLanguageService()->sL(
                        'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'
                    ))
                    ->setIcon($iconFactory->getIcon('actions-arrow-down-left', Icon::SIZE_SMALL))
            );
        }

        $buttonBar->addButton(
            $buttonBar
                ->makeLinkButton()
                ->setHref($this->controllerContext->getUriBuilder()->uriFor(
                    'refresh',
                    ['redirectUri' => $this->request->getRequestUri()]
                ))
                ->setTitle($this->getLanguageService()->getLL(
                    'hubspot_integration.customObjects.button.refreshSchemas'
                ))
                ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL))
                ->setShowLabelText(true)
        );
    }

    /**
     * List custom object schemas.
     */
    public function indexAction()
    {
        $this->view->assign('schemaLabels', $this->schemaRepository->findAllLabels());
    }

    /**
     * Inspect code for a schema.
     *
     * @param string $name
     */
    public function inspectAction(string $name)
    {
        $this->view->assign('schema', $this->schemaRepository->findByName($name));
    }

    /**
     * @param string $redirectUri
     */
    public function refreshAction(string $redirectUri)
    {
        $this->schemaRepository->findAll(false);

        $this->addFlashMessage(
            $this->getLanguageService()->getLL('hubspot_integration.customObjects.refreshSchemas.body'),
            $this->getLanguageService()->getLL('hubspot_integration.customObjects.refreshSchemas.title')
        );

        $this->redirectToUri($redirectUri);
    }
}
