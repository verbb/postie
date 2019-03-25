<?php
namespace verbb\postie\twigextensions;

use verbb\postie\helpers\PostieHelper;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;

class Extension extends Twig_Extension
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
            new Twig_SimpleFunction('get', [$this, 'getValueByKey']),
        ];
    }

    public function getValueByKey($array, $value)
    {
        if (is_array($array)) {
            return PostieHelper::getValueByKey($array, $value);
        }
    }
}
