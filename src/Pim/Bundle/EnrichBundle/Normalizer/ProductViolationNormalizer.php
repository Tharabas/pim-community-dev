<?php

namespace Pim\Bundle\EnrichBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductViolationNormalizer implements NormalizerInterface
{
    /** @var array */
    protected $supportedFormats = ['internal_api'];

    /**
     * {@inheritdoc}
     */
    public function normalize($violation, $format = null, array $context = [])
    {
        $path = $violation->getPropertyPath();
        $codeStart  = strpos($path, '[') + 1;
        $codeLength = strpos($path, ']') - $codeStart;
        $attributeInfo = substr($path, $codeStart, $codeLength);
        $attributeInfo = explode('-', $attributeInfo);

        $attributeCode = $attributeInfo[0];
        $locale = isset($attributeInfo[1]) ? $attributeInfo[1] : null;
        $scope = isset($attributeInfo[2]) ? $attributeInfo[2] : null;

        $normalizedViolation = [
            'attribute'     => $attributeCode,
            'locale'        => $locale,
            'scope'         => $scope,
            'message'       => $violation->getMessage()
        ];

        return $normalizedViolation;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ConstraintViolationInterface && in_array($format, $this->supportedFormats);
    }
}
