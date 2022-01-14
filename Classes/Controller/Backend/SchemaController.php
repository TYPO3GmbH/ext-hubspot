<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectSchemaRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\NoSuchCustomObjectSchemaException;
use T3G\Hubspot\Domain\Repository\Hubspot\PropertyRepository;
use T3G\Hubspot\Utility\CustomObjectUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for inspecting Hubspot custom object schemas.
 */
class SchemaController extends AbstractController
{
    /**
     * @var CustomObjectSchemaRepository
     */
    protected $schemaRepository;

    /**
     * @var PropertyRepository
     */
    protected $propertyRepository;

    /**
     * @param CustomObjectSchemaRepository|null $schemaRepository
     */
    public function __construct(
        CustomObjectSchemaRepository $schemaRepository = null,
        PropertyRepository $propertyRepository = null
    )
    {
        $this->schemaRepository = $schemaRepository
            ?? GeneralUtility::makeInstance(CustomObjectSchemaRepository::class);

        $this->propertyRepository = $propertyRepository
            ?? GeneralUtility::makeInstance(PropertyRepository::class);
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
                    ->setIcon($iconFactory->getIcon('actions-arrow-down-left', Icon::SIZE_SMALL)),
                ButtonBar::BUTTON_POSITION_LEFT,
                1
            );
        } else {
            $buttonBar->addButton(
                $buttonBar
                    ->makeLinkButton()
                    ->setHref($this->controllerContext->getUriBuilder()->uriFor(
                        'new',
                        ['redirectUri' => $this->request->getRequestUri()]
                    ))
                    ->setTitle($this->getLanguageService()->getLL(
                        'hubspot_integration.customObjects.button.newSchema'
                    ))
                    ->setShowLabelText(true)
                    ->setIcon($iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)),
                ButtonBar::BUTTON_POSITION_LEFT,
                1
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
                ->setShowLabelText(true),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
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

    /**
     * List schema files.
     */
    public function newAction()
    {
        $this->view->assign('files', CustomObjectUtility::getSchemaDefinitionFiles());
    }

    /**
     * @param int $file The file name's index int he schema definition.
     * @param bool $updateExisting If true, an existing JSON schema will be updated.
     */
    public function createAction(int $file, bool $updateExisting = false)
    {
        $filePath = GeneralUtility::getFileAbsFileName(CustomObjectUtility::getSchemaDefinitionFiles()[$file] ?? '');

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            $this->addFlashMessage(
                sprintf(
                    $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.error.noFileMessage'),
                    $filePath
                ),
                $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.error.noFileTitle'),
                FlashMessage::WARNING
            );

            $this->redirect('new');
        }

        $schema = json_decode($fileContent, true);

        if ($schema === null) {
            $this->addFlashMessage(
                sprintf(
                    $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.error.jsonDecodeMessage'),
                    $filePath
                ),
                $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.error.jsonDecodeTitle'),
                FlashMessage::WARNING
            );

            $this->redirect('new');
        }

        $existingSchema = $this->schemaRepository->findByName($schema['name'], false);

        if ($existingSchema !== null && !$updateExisting) {
            $this->view->assign('file', $file);
            $this->view->assign('existingSchema', $existingSchema);

            return;
        }

        if ($updateExisting) {
            foreach ($schema['properties'] ?? [] as $property) {
                $this->propertyRepository->update($schema['name'], $property['name'], $property);
            }

            $this->schemaRepository->update($schema['name'], $schema);

            $this->schemaRepository->findAll(false);

            $this->addFlashMessage(
                sprintf(
                    $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.updateSuccessMessage'),
                    $schema['name']
                ),
                $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.updateSuccessTitle'),
                FlashMessage::OK
            );

            $this->redirect('index');
        }

        $name = $this->schemaRepository->create($schema);

        $this->addFlashMessage(
            sprintf(
                $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.successMessage'),
                $name
            ),
            $this->getLanguageService()->getLL('hubspot_integration.customObjects.create.successTitle'),
            FlashMessage::OK
        );

        $this->redirect('index');
    }

    /**
     * Download a Custom Object Schema as a JSON file.
     *
     * @param string $name
     */
    public function downloadAction(string $name)
    {
        $schema = $this->schemaRepository->findByName($name, false);

        if ($schema === null) {
            throw new NoSuchCustomObjectSchemaException(
                'The schema "' . $schema . '" cannot be found.',
                1641985520229
            );
        }

        $jsonEncodedSchema = json_encode($schema);

        $headers = array(
            'Pragma' => 'public',
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Description' => 'File Transfer',
            'Content-Type'  => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $name . '.json"',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Length' => strlen($jsonEncodedSchema)
        );

        foreach ($headers as $header => $data) {
            $this->response->setHeader($header, $data);
        }

        $this->response->setContent($jsonEncodedSchema);

        return $this->response;
    }
}
