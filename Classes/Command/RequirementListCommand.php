<?php
/**
 * Created by PhpStorm.
 * User: gringer
 * Date: 03.01.18
 * Time: 07:31
 */

namespace GeorgRinger\RequirementChecker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class RequirementListCommand extends Command
{

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this->setDescription('Show requirements');
        $this->setHelp('Blabla');
    }

    /**
     * Executes the command for showing sys_log entries
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());


        $finder = new Finder();
        $finder
            ->depth(0)
            ->directories()
            ->in(PATH_site . 'typo3/sysext')
            ->sortByName();


        foreach ($finder as $fileObject) {
            $deps = $this->getDependenciesOfExt($fileObject->getPathname());

            $io->section($fileObject->getRelativePathname());
            $io->listing($deps);

        }
    }

    protected function getDependenciesOfExt(string $directory)
    {
        $totalNamespaces = $dependencies = [];

        try {
            $phpFilesOfExtension = new Finder();
            $phpFilesOfExtension
                ->in($directory . '/Classes')
                ->files()->name('*.php')
                ->depth('>= 0')
                ->sortByName();

            foreach ($phpFilesOfExtension as $fileObject) {
                $namespaces = $this->getNamespaces($fileObject->getPathname());
                $namespaces2 = $this->getNamespacesFromMakeInstance($fileObject->getContents());
                $totalNamespaces = array_merge($totalNamespaces, $namespaces, $namespaces2);


            }

            $this->removeOwnNamespaces($totalNamespaces, $directory);

            sort($totalNamespaces);

            $dependencies = $this->simplifyNamespaceList($totalNamespaces);
        } catch (\Exception $e) {

        }
        return $dependencies;
    }

    protected function getNamespacesFromMakeInstance(string $content): array
    {
        $namespaces = [];
        $tokens = token_get_all($content);
        foreach ($tokens as $k => $token) {
            if (is_array($token)) {
                if ($token[0] === T_STRING && $token[1] === 'makeInstance') {
                    $found = false;
                    $j = $k + 3;
                    for ($i = $k + 1; $i < $j; $i++) {
                        if ($found || !is_array($tokens[$i])) {
                            continue;
                        }
                        if (is_array($tokens[$i]) && $tokens[$i][0] === T_NS_SEPARATOR) {
                            $found = true;
                            $path = [];

                            $n = $i + 1;
                            while ($tokens[$n][0] !== T_PAAMAYIM_NEKUDOTAYIM) {
                                $path[] = $tokens[$n][1];
                                $n++;
                            }

                            $namespaces[] = implode('', $path);
                        }
                    }
                }
            }
        }

        return $namespaces;
    }

    protected function simplifyNamespaceList(array $namespaces)
    {
//        print_r($namespaces);
        $newList = [];
        foreach ($namespaces as $namespace) {
            $split = explode('\\', $namespace);
            if (count($split) > 2 && $split[0] === 'TYPO3' && $split[1] === 'CMS') {
                // skip other stuff
                if ($split[2] !== 'Fluid' && $split[2] !== 'Composer') {
                    $packageName = $split[2];
                    $newList[$packageName] = $packageName;
                }
            }
        }

        return array_values($newList);
    }

    protected function removeOwnNamespaces(array &$namespaces, string $directory)
    {
        $newList = [];
        $pathInfo = pathinfo($directory);
        $extensionDirectoryName = $pathInfo['basename'];

        $key = 'TYPO3\\CMS\\' . GeneralUtility::underscoredToUpperCamelCase($extensionDirectoryName) . '\\';
        foreach ($namespaces as $namespace) {
            if (!StringUtility::beginsWith($namespace, $key)) {
                $newList[] = $namespace;
            }
        }

        $namespaces = $newList;
    }

    protected function getNamespaces(string $path)
    {
        $namespaces = [];
        $handle = fopen($path, 'rb');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if (StringUtility::beginsWith($line, 'use ')) {
                    $namespaces[] = substr($line, 4);
                } elseif (StringUtility::beginsWith($line, 'class ')) {
                    continue;
                }

            }
        }
        return $namespaces;
    }
}
