<?php

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service bridge to TYPO3 instantiation and global instances.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusTypo3Bridge
{
    /**
     * Wrapper around {@see GeneralUtility::makeInstance()} to call via service
     * container expression.
     *
     * @param $className
     *
     * @return object
     */
    public function makeInstance($className)
    {
        return GeneralUtility::makeInstance($className);
    }

    /**
     * Get a TYPO3 global into the service container.
     *
     * @param $global
     *
     * @return mixed
     */
    public function getGlobal($global)
    {
        if (!isset($GLOBALS[$global])) {
            throw new \InvalidArgumentException(sprintf(
                'The global %s does not exist.',
                $global
            ));
        }

        return $GLOBALS[$global];
    }
}
