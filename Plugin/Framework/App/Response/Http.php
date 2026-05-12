<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\Plugin\Framework\App\Response;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin to modify cache headers for BFCache functionality
 */
class Http
{
    public const XML_PATH_ENABLE = 'system/bfcache/general/enable';
    public const XML_PATH_EXCLUDE_URL_PATTERNS = 'system/bfcache/scope/exclude_url_patterns';

    protected bool $isRequestCacheable = false;

    public function __construct(
        protected Config $config,
        protected ScopeConfigInterface $scopeConfig,
        protected HttpRequest $request
    ) {
    }

    /**
     * Intercept before setting no-cache headers to determine if request is cacheable
     *
     * @param ResponseHttp $subject
     * @return void
     */
    public function beforeSetNoCacheHeaders(ResponseHttp $subject): void
    {
        if ($this->config->getType() !== Config::BUILT_IN || !$this->isEnabled()) {
            return;
        }

        $cacheControlHeader = $subject->getHeader('Cache-Control');
        if (!$cacheControlHeader) {
            return;
        }

        $cacheControl = $cacheControlHeader->getFieldValue();
        $requestURI = ltrim($this->request->getRequestURI(), '/');

        if ($this->isRequestCacheable($cacheControl) && !$this->isRequestInExcludePatterns($requestURI)) {
            $this->isRequestCacheable = true;
        }
    }

    /**
     * Update cache headers after setting no-cache headers
     *
     * @param ResponseHttp $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterSetNoCacheHeaders(ResponseHttp $subject, mixed $result): mixed
    {
        if ($this->config->getType() !== Config::BUILT_IN || !$this->isEnabled()) {
            return $result;
        }

        $cacheControlHeader = $subject->getHeader('Cache-Control');
        if (!$cacheControlHeader) {
            return $result;
        }

        if ($this->isRequestCacheable === true) {
            $cacheControlHeader->removeDirective('no-store');
        }
        $this->isRequestCacheable = false;

        return $result;
    }

    /**
     * Check if request is cacheable based on cache control header
     *
     * @param string $cacheControl
     * @return bool
     */
    protected function isRequestCacheable(string $cacheControl): bool
    {
        // FPC hits will not have public or private cache control directives -- already processed
        if (!str_contains($cacheControl, 'public')
            && !str_contains($cacheControl, 'private')
            && !str_contains($cacheControl, 'no-store')) {
            return true;
        }

        // FPC misses will be cacheable if they have a public directive
        return (bool)preg_match('/public.*s-maxage=(\d+)/', $cacheControl);
    }

    /**
     * Check if the request URI contains any excluded URL patterns (case-insensitive, partial match).
     *
     * @param string $requestURI
     * @return bool
     */
    protected function isRequestInExcludePatterns(string $requestURI): bool
    {
        $patterns = $this->getConfig(self::XML_PATH_EXCLUDE_URL_PATTERNS);

        if ($patterns === '') {
            return false;
        }

        foreach ($this->parseExcludePatterns($patterns) as $pattern) {
            if ($pattern !== '' && mb_stripos($requestURI, $pattern, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse exclude patterns from config string.
     *
     * @param string $patterns
     * @return array
     */
    protected function parseExcludePatterns(string $patterns): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $patterns))));
    }

    /**
     * Check if BFCache is enabled
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get configuration value by path
     *
     * @param string $configPath
     * @param int|string|null $store
     * @return string
     */
    protected function getConfig(string $configPath, int|string|null $store = null): string
    {
        return (string)$this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
