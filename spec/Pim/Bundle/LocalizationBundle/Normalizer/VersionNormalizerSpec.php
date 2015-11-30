<?php

namespace spec\Pim\Bundle\LocalizationBundle\Normalizer;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\VersioningBundle\Model\Version;
use Pim\Component\Localization\LocaleResolver;
use Pim\Component\Localization\Presenter\PresenterAttributeConverterInterface;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class VersionNormalizerSpec extends ObjectBehavior
{
    function let(
        NormalizerInterface $versionNormalizer,
        PresenterAttributeConverterInterface $converter,
        LocaleResolver $localeResolver
    ) {
        $this->beConstructedWith(
            $versionNormalizer,
            $converter,
            $localeResolver
        );
    }

    function it_supports_version_normalization(Version $version)
    {
        $this->supportsNormalization($version, 'internal_api')->shouldReturn(true);
    }

    function it_normalize_fr_numbers(
        $version,
        $localeResolver,
        $versionNormalizer,
        $converter
    ) {
        $versionNormalizer->normalize($version, 'internal_api', Argument::any())->willReturn([
            'changeset' => [
                'maximum_frame_rate' => ['old' => '', 'new' => '200.7890'],
                'price-EUR'          => ['old' => '5.00', 'new' => '5.15'],
                'weight'             => ['old' => '', 'new' => '10.1234'],
            ]
        ]);
        $options = ['locale' => 'fr_FR'];
        $localeResolver->getCurrentLocale()->willReturn('fr_FR');

        $converter
            ->convert('maximum_frame_rate', '200.7890', $options)
            ->willReturn('200,7890');
        $converter
            ->convert('price', '5.00', $options)
            ->willReturn('5,00');
        $converter
            ->convert('price', '5.15', $options)
            ->willReturn('5,15');
        $converter
            ->convert('weight', '10.1234', $options)
            ->willReturn('10,1234');
        $converter
            ->convert(Argument::any(), '', $options)
            ->willReturn('');

        $this->normalize($version, 'internal_api')->shouldReturn([
            'changeset' => [
                'maximum_frame_rate' => ['old' => '', 'new' => '200,7890'],
                'price-EUR'          => ['old' => '5,00', 'new' => '5,15'],
                'weight'             => ['old' => '', 'new' => '10,1234'],
            ]
        ]);
    }
}
