<?php
namespace Oro\Bundle\DataModelBundle\Helper;

/**
 * Provides some utility methods to deal with locales
 *
 * TODO : should be move in a "core" bundle
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class LocaleHelper
{
    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * Default locale code
     * @var string
     */
    protected $defaultLocaleCode;

    /**
     * Current locale code
     * @var string
     */
    protected $currentLocaleCode;

    /**
     * Constructor
     *
     * @param ContainerInterface $container service container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Get default locale code (from configuration)
     * @return string
     */
    public function getDefaultLocaleCode()
    {
        if (!$this->defaultLocaleCode) {
            $this->defaultLocaleCode = $this->container->parameters['locale'];
        }

        return $this->defaultLocaleCode;
    }

    /**
     * Get current locale code (from http request or, if not defined, from configuration)
     * @return string
     */
    public function getCurrentLocaleCode()
    {
        if (!$this->currentLocaleCode) {
            if ($this->container->initialized('request') and $this->container->get('request')->getLocale()) {
                $this->currentLocaleCode = $this->container->get('request')->getLocale();
            } else if (!$this->currentLocaleCode) {
                $this->currentLocaleCode = $this->getDefaultLocaleCode();
            }
        }

        return $this->currentLocaleCode;
    }

}
