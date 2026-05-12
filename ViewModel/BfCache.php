<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * BFCache ViewModel
 * Provides data and configuration for BFCache templates.
 * Handles configuration management and business logic for frontend templates.
 */
class BfCache implements ArgumentInterface
{
    protected const XML_PATH_ENABLE_USER_INTERACTION_RELOAD_MINICART =
        'system/bfcache/general/enable_user_interaction_reload_minicart';

    protected const XML_PATH_AUTO_CLOSE_MENU_MOBILE =
        'system/bfcache/general/auto_close_menu_mobile';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Context $httpContext
    ) {
    }

    /**
     * Check if mini cart should reload on user interaction
     *
     * @return bool
     */
    public function isReloadMiniCartOnInteraction(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_USER_INTERACTION_RELOAD_MINICART,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if mobile menu should auto-close
     *
     * @return bool
     */
    public function autoCloseMenuMobile(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_AUTO_CLOSE_MENU_MOBILE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
