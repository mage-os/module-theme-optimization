<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use InvalidArgumentException;

class SpeculationRules implements ArgumentInterface
{
    protected const string CONFIG_PATH = 'system/speculation_rules/';
    protected const string MODE_PREFETCH = 'prefetch';
    protected const string MODE_PRERENDER = 'prerender';
    protected const string EAGERNESS_DEFAULT = 'moderate';
    protected const array FETCH_MODES = [self::MODE_PREFETCH, self::MODE_PRERENDER];
    protected const array EAGERNESS_MODES = ['conservative', 'moderate', 'eager'];

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected UrlInterface $urlBuilder,
        protected SerializerInterface $serializer,
        protected DesignInterface $viewDesign
    ) {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue('enable');
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        $mode = $this->getConfigValue('mode');

        if (in_array($mode, self::FETCH_MODES, true)) {
            return $mode;
        }

        return self::MODE_PREFETCH;
    }

    /**
     * @return string
     */
    public function getEagerness(): string
    {
        $eagerness = $this->getConfigValue('eagerness');

        if (in_array($eagerness, self::EAGERNESS_MODES, true)) {
            return $eagerness;
        }

        return self::EAGERNESS_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isPrerenderMode(): bool
    {
        return $this->getMode() === self::MODE_PRERENDER;
    }

    /**
     * @return string
     */
    public function getPrerenderingScript(): string
    {
        if (!$this->isPrerenderMode()) {
            return '';
        }

        $reloadAction = $this->isHyva()
            ? "window.dispatchEvent(new CustomEvent('reload-customer-section-data'));"
            : "require(['Magento_Customer/js/customer-data'], customerData => {
                    customerData.init();
               });";

        return <<<JS
        (() => {
            if (document.prerendering) {
                document.addEventListener("prerenderingchange", () => {
                    $reloadAction
                }, { once: true });
            }
        })();
        JS;
    }

    /**
     * @return array
     */
    public function getSpeculationRules(): array
    {
        return [
            $this->getMode() => [
                [
                    'source' => 'document',
                    'where' => $this->buildRules(),
                    'eagerness' => $this->getEagerness(),
                ],
            ],
        ];
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getSpeculationRulesJson(): string
    {
        return $this->serializer->serialize($this->getSpeculationRules());
    }

    /**
     * @return array
     */
    protected function buildRules(): array
    {
        $rules = [
            'and' => [
                ['href_matches' => '/*']
            ],
        ];

        $rules['and'][] = $this->getExcludedPaths();

        array_push($rules['and'], ...$this->getExcludedExtensions());
        array_push($rules['and'], ...$this->getExcludedSelectors());

        $rules['and'][] = ['not' => ['selector_matches' => '[rel=nofollow]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_blank]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_parent]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_top]']];

        return $rules;
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

        return $value === null ? null : (string)$value;
    }

    /**
     * @return array
     */
    public function getExcludedPaths(): array
    {
        $paths = explode("\n", (string)$this->getConfigValue('exclude_paths'));

        foreach ($paths as &$pattern) {
            $pattern = trim(trim($pattern), '/');
        }
        unset($pattern);
        $paths = array_filter($paths);

        if (empty($paths)) {
            return [];
        }

        return ['not' => ['href_matches' => '/*(' . implode('|', $paths) . ')/*']];
    }

    /**
     * @return array
     */
    public function getExcludedExtensions(): array
    {
        $extensions = explode(',', (string)$this->getConfigValue('exclude_extensions'));
        $extensions = array_filter(array_map('trim', $extensions));

        return array_map(
            static fn(string $extension): array => ['not' => ['href_matches' => sprintf('*.%s', ltrim($extension, '.'))]],
            array_values($extensions)
        );
    }

    /**
     * @return array
     */
    public function getExcludedSelectors(): array
    {
        $selectors = explode("\n", (string)$this->getConfigValue('exclude_selectors'));
        $selectors = array_filter(array_map('trim', $selectors));

        return array_map(
            static fn(string $selector): array => ['not' => ['selector_matches' => $selector]],
            array_values($selectors)
        );
    }

    /**
     * @return bool
     */
    protected function isHyva(): bool
    {
        $theme = $this->viewDesign->getDesignTheme();
        while ($theme) {
            if (str_starts_with((string)$theme->getCode(), 'Hyva/')) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }
        return false;
    }
}
