<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Error\ExceptionParser;
use T3G\Hubspot\Service\UsedFormsService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormController extends AbstractController
{
    /**
     * @var ExceptionParser
     */
    protected $exceptionParser;

    /**
     * @param ExceptionParser|null $exceptionParser
     */
    public function __construct(ExceptionParser $exceptionParser = null)
    {
        $this->exceptionParser = $exceptionParser ?? GeneralUtility::makeInstance(ExceptionParser::class);
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
}
