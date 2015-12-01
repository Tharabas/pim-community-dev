<?php

namespace Pim\Bundle\LocalizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that register localizers
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RegisterLocalizersPass implements CompilerPassInterface
{
    const LOCALIZATION_LOCALIZER_REGISTRY = 'pim_localization.localizer.registry';

    const LOCALIZATION_LOCALIZER_TAG = 'pim_localization.localizer';

    const LOCALIZATION_LOCALIZER_PRODUCT_VALUE_TAG = 'pim_localization.localizer.product_value';

    const LOCALIZATION_LOCALIZER_ATTRIBUTE_OPTION_TAG = 'pim_localization.localizer.attribute_option';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::LOCALIZATION_LOCALIZER_REGISTRY)) {
            return;
        }

        $definition = $container->getDefinition(self::LOCALIZATION_LOCALIZER_REGISTRY);

        $localizerTags = [
            self::LOCALIZATION_LOCALIZER_TAG                  => 'addLocalizer',
            self::LOCALIZATION_LOCALIZER_PRODUCT_VALUE_TAG    => 'addProductValueLocalizer',
            self::LOCALIZATION_LOCALIZER_ATTRIBUTE_OPTION_TAG => 'addAttributeOptionLocalizer',
        ];

        foreach ($localizerTags as $tag => $methodName) {
            foreach ($container->findTaggedServiceIds($tag) as $id => $localizer) {
                $definition->addMethodCall($methodName, [new Reference($id)]);
            }
        }
    }
}
