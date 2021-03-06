<?php
namespace TYPO3\Surf\Application\TYPO3;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Workflow;

/**
 * A TYPO3 CMS application template
 * @TYPO3\Flow\Annotations\Proxy(false)
 */
class CMS extends \TYPO3\Surf\Application\BaseApplication
{
    /**
     * Set the application production context
     *
     * @param string $context
     * @return CMS
     */
    public function setContext($context)
    {
        $this->options['context'] = trim($context);
        return $this;
    }

    /**
     * Get the application production context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->options['context'];
    }

    /**
     * Constructor
     * @param string $name
     */
    public function __construct($name = 'TYPO3 CMS')
    {
        parent::__construct($name);
        $this->options = array_merge($this->options, array(
            'context' => 'Production',
            'scriptFileName' => './typo3cms'
        ));
    }

    /**
     * Register tasks for this application
     *
     * @param \TYPO3\Surf\Domain\Model\Workflow $workflow
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @return void
     */
    public function registerTasks(\TYPO3\Surf\Domain\Model\Workflow $workflow, \TYPO3\Surf\Domain\Model\Deployment $deployment)
    {
        parent::registerTasks($workflow, $deployment);

        if ($deployment->hasOption('initialDeployment') && $deployment->getOption('initialDeployment') === true) {
            $workflow->addTask('TYPO3\\Surf\\Task\\DumpDatabaseTask', 'initialize', $this);
            $workflow->addTask('TYPO3\\Surf\\Task\\RsyncFoldersTask', 'initialize', $this);
        }

        $workflow
                ->afterStage(
                    'update',
                    array(
                        'TYPO3\\Surf\\Task\\TYPO3\\CMS\\SymlinkDataTask',
                        'TYPO3\\Surf\\Task\\TYPO3\\CMS\\CopyConfigurationTask'
                    ), $this
                )
                ->addTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CompareDatabaseTask', 'migrate', $this)
                ->afterStage('switch', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask', $this);
    }

    /**
     * @param Workflow $workflow
     * @param string $packageMethod
     */
    protected function registerTasksForPackageMethod(Workflow $workflow, $packageMethod)
    {
        parent::registerTasksForPackageMethod($workflow, $packageMethod);
        switch ($packageMethod) {
            case 'git':
                $workflow->afterTask('TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\CreatePackageStatesTask', $this);
                break;
        }
    }
}
