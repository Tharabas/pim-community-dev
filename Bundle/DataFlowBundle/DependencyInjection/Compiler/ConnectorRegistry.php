<?php
namespace Oro\Bundle\DataFlowBundle\DependencyInjection\Compiler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataFlowBundle\Job\JobInterface;
use Oro\Bundle\DataFlowBundle\Connector\ConnectorInterface;

/**
 * Aims to register all connectors
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class ConnectorRegistry
{
    /**
     * Connectors references
     * @var \ArrayAccess
     */
    protected $connectors;

    /**
     * Jobs references
     * @var \ArrayAccess
     */
    protected $jobs;

    /**
     * Connector to jobs aliases
     * @var \ArrayAccess
     */
    protected $connectorToJobs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connectors      = array();
        $this->jobs            = array();
        $this->connectorToJobs = array();
    }

    /**
     * Add a job to a connector
     *
     * @param string             $connectorId the connector id
     * @param ConnectorInterface $connector   the connector
     * @param string             $jobId       the job id
     * @param JobInterface       $job         the job
     *
     * @return ConnectorRegistry
     */
    public function addJobToConnector($connectorId, ConnectorInterface $connector, $jobId, JobInterface $job)
    {
        $this->connectors[$connectorId] = $connector;
        $this->jobs[$jobId] = $job;
        if (!isset($this->connectorToJobs[$connectorId])) {
            $this->connectorToJobs[$connectorId] = array();
        }
        $this->connectorToJobs[$connectorId][] = $jobId;

        return $this;
    }

    /**
     * Get the list of connectors
     *
     * @return multitype:ConnectorInterface
     */
    public function getConnectors()
    {
        return $this->connectors;
    }

    /**
     * Get the list of jobs
     *
     * @return multitype:JobInterface
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Get the associative array of connectors aliases to jobs aliases
     *
     * @return multitype
     */
    public function getConnectorToJobs()
    {
        return $this->connectorToJobs;
    }
}
