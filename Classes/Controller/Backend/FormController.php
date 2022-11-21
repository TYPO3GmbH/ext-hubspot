<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Error\ExceptionParser;
use T3G\Hubspot\Service\UsedFormsService;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormController extends AbstractController
{
    /**
     * @var ExceptionParser
     */
    protected $exceptionParser;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @param ExceptionParser|null $exceptionParser
     * @param IconFactory|null $iconFactory
     */
    public function __construct(ExceptionParser $exceptionParser = null, IconFactory $iconFactory = null)
    {
        $this->exceptionParser = $exceptionParser ?? GeneralUtility::makeInstance(ExceptionParser::class);
        $this->iconFactory = $iconFactory ?? GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Render all forms in use
     */
    public function indexAction()
    {
        try {
            $usedFormsService = GeneralUtility::makeInstance(UsedFormsService::class);
            $formsInUse = $usedFormsService->getFormsInUseWithDetails();
            $this->view->assignMultiple([
                'formsInUse' => $formsInUse,
                'returnUrl' => urlencode($this->request->getRequestUri()),
            ]);
        } catch (BadRequest $badRequest) {
            $message = $this->exceptionParser->getBadRequestMessage($badRequest);
            $this->addFlashMessage($message, 'Bad Request', FlashMessage::ERROR);
            $this->redirect('index');
        }
    }

    public function editInlineAction(string $hubspotGuid)
    {
        $documentHeader = $this->moduleTemplate->getDocHeaderComponent();
        $buttonBar = $documentHeader->getButtonBar();

        $button = $buttonBar->makeLinkButton()
            ->setHref($this->uriBuilder->uriFor('index', null, 'Backend\\Form'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back'))
            ->setShowLabelText(true);
        $buttonBar->addButton($button);

        $this->view
            ->assign('portalId', (int)$_ENV['APP_HUBSPOT_PORTALID'])
            ->assign('hubspotGuid', $hubspotGuid);
    }
}
