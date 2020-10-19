<?php
declare(strict_types = 1);
namespace T3G\Hubspot\ViewHelpers\Compatibility\Backend;

/*
 * This file is part of the package t3g/hubspot.
 *
 * Original file is available as a part of TYPO3 Core (ext:backend) from v9 LTS
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Compatibility with TYPO3 <9.3. Create internal link within backend.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *     <form action="{be:moduleLink(route:'pages_new', arguments:'{id:pageUid}')}" method="post">
 *         <!-- form content -->
 *     </form>
 *
 * Output::
 *
 *     <form action="/pages/new" method="post">
 *         <!-- form content -->
 *     </form>
 */
class ModuleLinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('route', 'string', 'The route to link to', true);
        $this->registerArgument('arguments', 'array', 'Additional link arguments', false, []);
        $this->registerArgument('query', 'string', 'Additional link arguments as string');
        $this->registerArgument('currentUrlParameterName', 'string', 'Add current url as given parameter');
    }

    /**
     * Render module link with arguments
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $parameters = $arguments['arguments'];
        if ($arguments['query'] !== null) {
            ArrayUtility::mergeRecursiveWithOverrule($parameters, GeneralUtility::explodeUrl2Array($arguments['query']));
        }
        if ($arguments['currentUrlParameterName'] !== null) {
            $parameters[$arguments['currentUrlParameterName']] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        return (string)$uriBuilder->buildUriFromRoute($arguments['route'], $parameters);
    }
}
