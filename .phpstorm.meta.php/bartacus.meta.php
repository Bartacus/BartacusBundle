<?php

namespace PHPSTORM_META {

    override(\Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge::makeInstance(0), map([]));
    override(\Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge::getGlobal(0), map([
        'BE_USER' => \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class,
        'FE_USER' => \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::class,
        'TSFE' => \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
        'TYPO3_DB' => \TYPO3\CMS\Core\Database\DatabaseConnection::class,
        '' => '',
    ]));
}
