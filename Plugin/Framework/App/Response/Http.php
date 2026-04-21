<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\Plugin\Framework\App\Response;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\ScopeInterface;

class Http
{
    public const string XML_PATH_ENABLE = 'system/bfcache/general/enable';
    public const string XML_PATH_EXCLUDE_URL_PATTERNS = 'system/bfcache/scope/exclude_url_patterns';

    protected bool $isRequestCacheable = false;

    public function __construct(
        protected Config $config,
        protected ScopeConfigInterface $scopeConfig,
        protected HttpRequest $request
    ) {
    }

    /**
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
     * @param string $cacheControl
     * @return bool
     */
    protected function isRequestCacheable(string $cacheControl): bool
    {
        if (!str_contains($cacheControl, 'public')
            && !str_contains($cacheControl, 'private')
            && !str_contains($cacheControl, 'no-store')) {
            return true;
        }

        return (bool)preg_match('/public.*s-maxage=(\d+)/', $cacheControl);
    }

    /**
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
     * @param string $patterns
     * @return array
     */
    protected function parseExcludePatterns(string $patterns): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $patterns))));
    }

    /**
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
