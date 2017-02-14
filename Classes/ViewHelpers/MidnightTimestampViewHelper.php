<?php
declare(strict_types = 1);
namespace T3G\Hubspot\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Scope: frontend
 *
 * Puts a timestamp of the current date UTC at midnight * 1000
 *
 * this is exactly how hubspot requires a datetime
 */
class MidnightTimestampViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = true;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
    }

    /**
     * @param array $arguments
     * @param callable|\Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTime(0, 0, 0);
        $value = $date->getTimestamp() * 1000;
        return (string)$value;
    }
}
