<?php

namespace Pim\Bundle\EnrichBundle\Normalizer;

use Pim\Component\Catalog\Model\AttributeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Completeness normalizer
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CompletenessCollectionNormalizer implements NormalizerInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @param NormalizerInterface $normalizer
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($completenesses, $format = null, array $context = [])
    {
        $locales = array_keys($completenesses);
        foreach ($completenesses as $locale => $channels) {
            foreach ($channels['channels'] as $channel => $completeness) {
                $completenesses[$locale]['channels'][$channel] = $this->normalizeCompleteness(
                    $completeness,
                    $locales,
                    $format,
                    $context
                );
            }
        }

        return $completenesses;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return false;
    }

    /**
     * Normalize a completeness element
     *
     * @param array  $completeness
     * @param array  $locales
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    protected function normalizeCompleteness($completeness, $locales, $format = null, array $context = [])
    {
        $missing = [];
        foreach ($completeness['missing'] as $attribute) {
            $missing[] = [
                'code'   => $attribute->getCode(),
                'labels' => $this->normalizeAttributeLabels($attribute, $locales)
            ];
        }

        return [
            'missing'      => $missing,
            'completeness' => $this->normalizer->normalize($completeness['completeness'], $format, $context)
        ];
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $locales
     *
     * @return array
     */
    protected function normalizeAttributeLabels(AttributeInterface $attribute, array $locales)
    {
        $labels = [];
        foreach ($locales as $locale) {
            $labels[$locale] = $attribute->getTranslation($locale)->getLabel();
        }

        return $labels;
    }
}
