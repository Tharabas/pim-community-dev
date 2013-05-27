<?php
namespace Oro\Bundle\DataFlowBundle\Tests\Unit\Connector;

use Oro\Bundle\DataFlowBundle\Tests\Unit\Connector\Demo\MyConnector;
use Oro\Bundle\DataFlowBundle\Tests\Unit\Configuration\Demo\MyConfiguration;
use Oro\Bundle\DataFlowBundle\Tests\Unit\Configuration\Demo\MyOtherConfiguration;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class ConnectorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MyConnector
     */
    protected $connector;

    /**
     * @var string
     */
    protected $configurationName;

    /**
     * Setup
     */
    public function setup()
    {
        $this->configurationName = 'Oro\Bundle\DataFlowBundle\Tests\Unit\Configuration\Demo\MyConfiguration';
        $this->connector = new MyConnector($this->configurationName);
    }

    /**
     * Test related method
     */
    public function testConfigure()
    {
        $this->assertNull($this->connector->getConfiguration());
        $this->assertEquals($this->connector->getConfigurationName(), $this->configurationName);
        $configuration = new MyConfiguration();
        $this->connector->configure($configuration);
        $this->assertEquals($this->connector->getConfiguration(), $configuration);
        $this->assertEquals($this->connector->getConfigurationName(), $this->configurationName);
    }

    /**
     * Test related method
     * @expectedException \Oro\Bundle\DataFlowBundle\Exception\ConfigurationException
     */
    public function testConfigureException()
    {
        $configuration = new MyOtherConfiguration();
        $this->connector->configure($configuration);
    }
}
