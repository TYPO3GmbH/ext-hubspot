<?php
declare(strict_types = 1);


namespace T3G\Hubspot\ViewHelpers\Form;


use TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper;

class HubspotPropertiesViewHelper extends SelectViewHelper
{

    protected function getOptions()
    {
        $options = parent::getOptions();
        $options['contact'] = 'Contact';
        $options['deal'] = 'Deal';
        $options['company'] = 'Company';

        return $options;
    }
}