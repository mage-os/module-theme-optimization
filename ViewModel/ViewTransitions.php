<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class ViewTransitions implements ArgumentInterface
{
    protected const CONFIG_PATH = 'system/view_transitions/';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function getConfigValue(string $key): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH . $key,
            ScopeInterface::SCOPE_STORE
        );

        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue('enable');
    }

    /**
     * @return bool
     */
    public function isEnabledForBfcache(): bool
    {
        return (bool)$this->getConfigValue('enable_for_bfcache');
    }
}
