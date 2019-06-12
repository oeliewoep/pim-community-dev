<?php
declare(strict_types=1);

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Factory\Read\Value;

use Akeneo\Pim\Enrichment\Component\Product\Factory\Read\Value\ReadValueFactory;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\Attribute;
use PhpSpec\ObjectBehavior;

/**
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class TextValueFactorySpec extends ObjectBehavior
{
    public function it_is_a_read_value_factory()
    {
        $this->shouldBeAnInstanceOf(ReadValueFactory::class);
    }

    public function it_supports_text_attribute_type()
    {
        $this->supportedAttributeType()->shouldReturn(AttributeTypes::TEXT);
    }

    public function it_creates_a_localizable_and_scopable_value()
    {
        $attribute = $this->getAttribute(true, true);
        /** @var ScalarValue $value */
        $value = $this->create($attribute, 'ecommerce', 'fr_FR', 'a_text');
        $value->isLocalizable()->shouldBe(true);
        $value->isScopable()->shouldBe(true);
        $value->getData()->shouldBe('a_text');
    }

    public function it_creates_a_localizable_value()
    {
        $attribute = $this->getAttribute(true, false);
        /** @var ScalarValue $value */
        $value = $this->create($attribute, null, 'fr_FR', 'a_text');
        $value->isLocalizable()->shouldBe(true);
        $value->isScopable()->shouldBe(false);
        $value->getData()->shouldBe('a_text');
    }

    public function it_creates_a_scopable_value()
    {
        $attribute = $this->getAttribute(false, true);
        /** @var ScalarValue $value */
        $value = $this->create($attribute, 'ecommerce', null, 'a_text');
        $value->isLocalizable()->shouldBe(false);
        $value->isScopable()->shouldBe(true);
        $value->getData()->shouldBe('a_text');
    }

    public function it_creates_a_non_localizable_and_non_scopable_value()
    {
        $attribute = $this->getAttribute(false, false);
        /** @var ScalarValue $value */
        $value = $this->create($attribute, null, null, 'a_text');
        $value->isLocalizable()->shouldBe(false);
        $value->isScopable()->shouldBe(false);
        $value->getData()->shouldBe('a_text');
    }

    private function getAttribute(bool $isLocalizable, bool $isScopable): Attribute
    {
        return new Attribute('an_attribute', AttributeTypes::TEXT, [], $isLocalizable, $isScopable, null, false);
    }
}
