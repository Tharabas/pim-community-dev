<?php
namespace Oro\Bundle\FlexibleEntityBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\FlexibleEntityBundle\DependencyInjection\Compiler\AddManagerCompilerPass;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class AddManagerCompilerPassTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConnectorCompilerPass
     */
    protected $compiler;

    /**
     * @var ContainerBuilder
     */
    protected $builder;

    /**
     * Setup
     */
    public function setup()
    {
        $this->compiler = new AddManagerCompilerPass();

        $this->builder = new ContainerBuilder();
        $defRegistry = new Definition('Oro\Bundle\DataFlowBundle\Manager\FlexibleManagerRegistry');
        $definitions = array('oro_flexibleentity.registry' => $defRegistry);
        $this->builder->setDefinitions($definitions);
    }

    /**
     * Test related method
     */
    public function testProcess()
    {
        $this->compiler->process($this->builder);
    }
}
