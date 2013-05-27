<?php
namespace Oro\Bundle\FlexibleEntityBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * A registry which knows all flexible entity managers
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class FlexibleManagerRegistry
{
    /**
     * Managers references
     * @var \ArrayAccess
     */
    protected $managers;

    /**
     * Entity name to manager
     * @var \ArrayAccess
     */
    protected $entityToManager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->managers        = array();
        $this->entityToManager = array();
    }

    /**
     * Add a manager to the registry
     *
     * @param string          $managerId  the manager id
     * @param FlexibleManager $manager    the manager
     * @param string          $entityFQCN the FQCN
     *
     * @return ManagerRegistry
     */
    public function addManager($managerId, FlexibleManager $manager, $entityFQCN)
    {
        $this->managers[$managerId]        = $manager;
        $this->entityToManager[$entityFQCN]= $manager;

        return $this;
    }

    /**
     * Get the list of manager id to manager services
     *
     * @return array
     */
    public function getManagers()
    {
        return $this->managers;
    }

    /**
     * Get the list of entity FQCN to related manager
     *
     * @return array
     */
    public function getEntityToManager()
    {
        return $this->entityToManager;
    }

    /**
     * Get the manager from the entity FQCN
     *
     * @param string $entityFQCN the entity FQCN
     *
     * @return FlexibleManager
     */
    public function getManager($entityFQCN)
    {
        return $this->entityToManager[$entityFQCN];
    }
}
