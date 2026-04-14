<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) Emily Karisch
 *
 * The BartacusBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The BartacusBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the BartacusBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\StaticRoute\EventSubscriber;

use Bartacus\Bundle\BartacusBundle\StaticRoute\Event\StaticRouteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

class RobotsTxtSubscriber implements EventSubscriberInterface
{
    /**
     * Collection of all bad user agents which are listed in the /robots.txt file and should be disallowed to access any content.
     */
    private const array BAD_USER_AGENTS = [
        'IsraBot',
        'UbiCrawler',
        'DOC',
        'Zao',
        'sitecheck.internetseer.com',
        'Zealbot',
        'MSIECrawler',
        'SiteSnagger',
        'WebStripper',
        'WebCopier',
        'Fetch',
        'Offline Explorer',
        'Teleport',
        'TeleportPro',
        'WebZIP',
        'linko',
        'HTTrack',
        'Microsoft.URL.Control',
        'Xenu',
        'larbin',
        'libwww',
        'ZyBORG',
        'Download Ninja',
        'wget',
        'grub-client',
        'k2spider',
        'NPBot',
        'WebReaper',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            StaticRouteEvent::EVENT_NAME => [['handleRobotsTxt', 8]],
        ];
    }

    public function handleRobotsTxt(StaticRouteEvent $event): void
    {
        if ('robots.txt' !== $event->getRoute()) {
            return;
        }

        $content = [];

        // add the absolute path to all sitemap.xml files
        foreach ($event->getSite()->getLanguages() as $siteLanguage) {
            $uri = $siteLanguage->getBase();
            $content[] = 'Sitemap: '.mb_rtrim($uri->getScheme().'//'.$uri->getHost().$uri->getPath(), '/').'/sitemap.xml';
        }

        $content[] = "\n";

        // default user agent disallow / allow
        $content[] = 'User-agent: *';
        $content[] = 'Disallow: /typo3/';
        $content[] = 'Disallow: /typo3conf/';
        $content[] = 'Allow: /';
        $content[] = 'Allow: /typo3conf/ext/';
        $content[] = 'Allow: /typo3temp/';
        $content[] = 'Allow: /typo3/sysext/frontend/Resources/Public/*';
        $content[] = "\n";

        // disallow the access for all bad user agents
        foreach (self::BAD_USER_AGENTS as $badUserAgent) {
            $content[] = 'User-agent: '.$badUserAgent;
            $content[] = 'Disallow: /';
        }

        $event->setResponse(new HtmlResponse(
            implode("\n", $content),
            200,
            ['Content-Type' => 'text/plain; charset=utf-8']
        ));
    }
}
