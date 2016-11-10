<?php

/*
 * This file is part of the BartacusBundle.
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

declare(strict_types=1);

namespace Bartacus\Bundle\BartacusBundle\Command;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @DI\Service()
 * @DI\Tag("console.command")
 */
class PrepareWebDirCommand extends Command
{
    /**
     * @var string
     */
    private $vendorDir;

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @DI\InjectParams(params={
     *      "vendorDir" = @DI\Inject("%bartacus.paths.vendor_dir%"),
     *      "webDir" = @DI\Inject("%bartacus.paths.web_dir%"),
     *      "filesystem" = @DI\Inject("filesystem"),
     * })
     */
    public function __construct(string $vendorDir, string $webDir, Filesystem $filesystem)
    {
        $this->vendorDir = rtrim(realpath($vendorDir), DIRECTORY_SEPARATOR);
        $this->webDir = rtrim(realpath($webDir), DIRECTORY_SEPARATOR);
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('bartacus:web-dir:prepare')
            ->setDescription('Prepares the needed TYPO3 symlinks in the web dir')
        ;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $target = $this->webDir.'/typo3/sysext';
        $source = $this->filesystem->makePathRelative(
            $this->vendorDir.'/typo3/cms/typo3/sysext',
            $this->webDir.'/typo3/'
        );

        try {
            $this->filesystem->symlink($source, $target);

            return;
        } catch (IOException $e) {
            // on error we give it a second try with copy on windows below
        }

        $this->filesystem->symlink($source, $target, true);
    }
}
