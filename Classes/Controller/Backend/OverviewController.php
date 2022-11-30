<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use T3G\Hubspot\Domain\Repository\Hubspot\AccountInformationRepository;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OverviewController extends AbstractController
{
    /**
     * Render overview of available hubspot integration backend modules
     */
    public function indexAction()
    {
        $this->view->assignMultiple(
            [
                'accountDetails' => $this->getAccountDetails(),
                'applicationContext' => $this->getApplicationContext(),
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

    /**
     * Get HubSpot account information details.
     *
     * @return array
     */
    protected function getAccountDetails(): array
    {
        /** @var AccountInformationRepository $accountInformationRepository */
        $accountInformationRepository = GeneralUtility::makeInstance(AccountInformationRepository::class);
        try {
            $accountDetails = $accountInformationRepository->getAccountDetails();
        } catch (\Throwable $th) {
            $accountDetails = [];
        }

        return $accountDetails;
    }

    /**
     * Get TYPO3 application context information.
     *
     * @return array
     */
    protected function getApplicationContext(): array
    {
        /** @var ApplicationContext $applicationContext  */
        $applicationContext = \TYPO3\CMS\Core\Core\Environment::getContext();

        return [
            'isDevelopment' => $applicationContext->isDevelopment(),
            'isProduction' => $applicationContext->isProduction(),
            'applicationContext' => (string)$applicationContext,
        ];
    }
}
