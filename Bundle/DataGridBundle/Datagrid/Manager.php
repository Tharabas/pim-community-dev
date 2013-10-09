<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;

/**
 * Class Manager
 * @package Oro\Bundle\DataGridBundle\Datagrid
 *
 * Responsibility of this class is to store raw config data, prepare configs for datagrid builder.
 * Public interface returns datagrid object prepared by builder using config
 */
class Manager implements ManagerInterface
{
    /** @var Builder */
    protected $datagridBuilder;

    /** @var array */
    protected $rawConfiguration;

    /** @var array */
    protected $processedConfiguration;

    public function __construct(array $rawConfiguration, Builder $builder)
    {
        $this->rawConfiguration = $rawConfiguration;
        $this->datagridBuilder  = $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid($name)
    {
        $this->getDatagridBuilder()->build($this->getConfigurationForGrid($name));
    }

    /**
     * Internal getter for builder
     *
     * @return Builder
     */
    protected function getDatagridBuilder()
    {
        return $this->datagridBuilder;
    }

    /**
     * Returns prepared config for requested datagrid
     * Throws exception in case when datagrid configuration not found
     * Cache prepared config in case if datagrid requested few times
     *
     * @param string $name
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function getConfigurationForGrid($name)
    {
        if (!isset($this->rawConfiguration[$name])) {
            throw new \RuntimeException(sprintf('Configuration for datagrid %s not found', $name));
        }

        if (!isset($this->processedConfiguration[$name])) {
            $result = $this->rawConfiguration[$name];

            // @TODO process configuration here
        }

        return $this->processedConfiguration[$name];
    }
}
