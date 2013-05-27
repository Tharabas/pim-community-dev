<?php

namespace Oro\Bundle\GridBundle\Property;

use Symfony\Component\Routing\Router;
use Oro\Bundle\GridBundle\Datagrid\ResultRecordInterface;

class UrlProperty extends AbstractProperty
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var router
     */
    protected $router;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var array
     */
    protected $placeholders;

    /**
     * @var bool
     */
    protected $isAbsolute;

    /**
     * @param string $name
     * @param Router $router
     * @param string $routeName
     * @param array $placeholders
     * @param bool $isAbsolute
     */
    public function __construct($name, Router $router, $routeName, array $placeholders = array(), $isAbsolute = false)
    {
        $this->name = $name;
        $this->router = $router;
        $this->routeName = $routeName;
        $this->placeholders = $placeholders;
        $this->isAbsolute = $isAbsolute;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(ResultRecordInterface $record)
    {
        return $this->router->generate($this->routeName, $this->getParameters($record), $this->isAbsolute);
    }

    /**
     * Get route parameters from record
     *
     * @param ResultRecordInterface $record
     * @return array
     */
    protected function getParameters(ResultRecordInterface $record)
    {
        $result = array();
        foreach ($this->placeholders as $name => $dataKey) {
            if (is_numeric($name)) {
                $name = $dataKey;
            }
            $result[$name] = $record->getValue($dataKey);
        }
        return $result;
    }
}
