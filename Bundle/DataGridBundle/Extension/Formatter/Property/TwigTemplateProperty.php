<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Extension\Formatter\ResultRecordInterface;

class TwigTemplateProperty extends AbstractProperty
{
    /** @var \Twig_Environment */
    protected $environment;

    /** @var \Twig_TemplateInterface */
    protected $template;

    /**  @var array */
    protected $reservedKeys = ['record', 'value'];

    public function __construct(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $params)
    {
        parent::init($params);
        $checkInvalidArgument = array_intersect(array_keys($this->getOr('context', [])), $this->reservedKeys);
        if (count($checkInvalidArgument)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Context of template "%s" includes reserved key(s) - (%s)',
                    $this->get('template'),
                    implode(', ', array_values($checkInvalidArgument))
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(ResultRecordInterface $record)
    {
        $context = array_merge(
            $this->getOr('context', []),
            array(
                'record' => $record,
                'value'  => $record->getValue($this->get(self::NAME_KEY)),
            )
        );

        return $this->getTemplate()->render($context);
    }

    /**
     * Load twig template
     *
     * @return \Twig_TemplateInterface
     */
    protected function getTemplate()
    {
        if (!$this->template) {
            $this->template = $this->environment->loadTemplate($this->get('template'));
        }

        return $this->template;
    }
}
