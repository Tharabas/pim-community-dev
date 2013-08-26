<?php

namespace Pim\Bundle\VersioningBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Pim\Bundle\VersioningBundle\Entity\VersionableInterface;
use Pim\Bundle\VersioningBundle\Entity\Version;

/**
 * Audit manager
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AuditManager
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * {@inheritDoc}
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Return product logs
     *
     * @param VersionableInterface $versionable
     *
     * @return ArrayCollection
     */
    public function getLogEntries(VersionableInterface $versionable)
    {
        $repo = $this->em->getRepository('Oro\Bundle\DataAuditBundle\Entity\Audit');
        $logs = $repo->getLogEntries($versionable);

        return $logs;
    }

    /**
     * Return first log entry
     *
     * @param VersionableInterface $versionable
     *
     * @return Audit
     */
    public function getFirstLogEntry(VersionableInterface $versionable)
    {
        $logs = $this->getLogEntries($versionable);

        return (!empty($logs)) ? current($logs) : null;
    }

    /**
     * Return last log entry
     *
     * @param VersionableInterface $versionable
     *
     * @return Audit
     */
    public function getLastLogEntry(VersionableInterface $versionable)
    {
        $logs = $this->getLogEntries($versionable);

        return (!empty($logs)) ? end($logs) : null;
    }

    /**
     * Create a log entry from current and previous version
     *
     * @param Version $current
     * @param Version $previous
     *
     * @return Audit
     */
    public function buildAudit(Version $current, Version $previous = null)
    {
        $newData = $current->getData();
        if ($previous) {
            $oldData = $previous->getData();
        } else {
            $oldData = array();
        }

        $diff = array_diff($newData, $oldData);
        $diffData = array();
        foreach (array_keys($diff) as $changedField) {
            if (isset($oldData[$changedField])) {
                $diffData[$changedField]= array('old' => $oldData[$changedField]);
            } else {
                $diffData[$changedField]= array('old' => '');
            }
            if (isset($newData[$changedField])) {
                $diffData[$changedField]['new'] = $newData[$changedField];
            } else {
                $diffData[$changedField]['new'] = '';
            }
            if (empty($diffData[$changedField]['new']) and empty($diffData[$changedField]['old'])) {
                unset($diffData[$changedField]);
            } elseif ($diffData[$changedField]['new'] == $diffData[$changedField]['old']) {
                unset($diffData[$changedField]);
            }
        }

        $previousAudit = $this->em->getRepository('Oro\Bundle\DataAuditBundle\Entity\Audit')
            ->findOneBy(
                array('objectId' => $current->getResourceId(), 'objectName' => $current->getResourceName()),
                array('loggedAt' => 'desc')
            );
        if ($previousAudit) {
            $versionNumber = $previousAudit->getVersion() + 1;
        } else {
            $versionNumber = 1;
        }
        $action = ($versionNumber > 1) ? 'update' : 'create';
        $audit = new Audit();
        $audit->setAction($action);
        $audit->setObjectClass($current->getResourceName());
        $audit->setLoggedAt();
        $audit->setObjectName($current->getResourceName());
        $audit->setObjectId($current->getResourceId());
        $audit->setVersion($versionNumber);
        $audit->setData($diffData);
        $audit->setUser($current->getUser());

        return $audit;
    }
}
