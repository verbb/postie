<?php
namespace verbb\postie\twigextensions;

use verbb\postie\helpers\PostieHelper;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'getValueByKey';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get', [$this, 'getValueByKey']),
        ];
    }

    public function getValueByKey($array, $value)
    {
        if (is_array($array)) {
            return PostieHelper::getValueByKey($array, $value);
        }

        return null;
    }
}
