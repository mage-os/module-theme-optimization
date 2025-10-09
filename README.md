[![Latest Stable Version](https://poser.pugx.org/mage-os/module-theme-optimization/v/stable)](https://packagist.org/packages/mage-os/module-theme-optimization)
[![License](https://poser.pugx.org/mage-os/module-theme-optimization/license)](https://packagist.org/packages/mage-os/module-theme-optimization)
[![Total Downloads](https://poser.pugx.org/mage-os/module-theme-optimization/downloads)](https://packagist.org/packages/mage-os/module-theme-optimization)

<p align="center">
    <a href="https://mage-os.org"><img alt="Mage-OS" src="https://mage-os.org/wp-content/uploads/2023/01/mage-os-logo.webp" width="250"></a>
</p>

# MageOS_ThemeOptimization module

This module provides theme-related features to improve the performance of your Magento store, including:

* Back/Forward Cache support for faster browser navigation
* Page transitions when navigating between pages on Magento
* Speculative preloading of internal links on hover

## Requirements

* Magento 2.4.5+ or equivalent version of Adobe Commerce, Adobe Commerce Cloud, or Mage-OS

## Installation details

To install the module, run the following commands in SSH, from the Magento root directory:

```bash
composer require mage-os/module-theme-optimization
php bin/magento setup:upgrade
```

## Configuration

The module provides settings in the Magento Admin Panel under: **Stores > Configuration > Advanced > System**

All values can be configured at Default, Website, and Store View scopes.

### Back/Forward Cache

* **Enable Back/Forward Cache** - Enable back/forward cache to store pages in browser memory temporarily for faster navigation. (Default: Yes)
* **Update Mini Cart on User Interaction**
  - Yes: Mini cart updates only after user interaction when page is restored from cache (Default)
  - No: Mini cart updates immediately on page restore
  - Recommended "Yes" to maintain optimal Page Speed and Core Web Vitals scores
* **Auto Close Menu** - Automatically close open menus when page is restored from back/forward cache (for compatible themes). (Default: Yes)
* **Exclude URLs** - Optional configuration to exclude specific URL patterns from back/forward cache. Enter URL parts (substring), one per line. The extension automatically excludes non-cacheable URLs, so this is only needed for custom cached URLs that load private data via JavaScript.

> [!IMPORTANT]  
> bfcache availability varies based on your Full Page Cache engine and hosting. Some require additional setup.

<details>
<summary><strong>READ MORE: Configuring Back/Forward cache with Varnish FPC</strong></summary>

For the Back/Forward Cache feature to work with Varnish Full Page Cache, you must modify your VCL file's `vcl_deliver` subroutine by updating the existing Cache-Control header logic.

```vcl
sub vcl_deliver {
  # Find the existing line that sets Cache-Control, like:
  set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
  
  # Replace it with:
  if (resp.http.Cache-Control ~ "public") {
      set resp.http.Cache-Control = "no-cache, must-revalidate, max-age=0";
  } else {
      set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
  }
}
```

- This modification requires manual VCL file editing and Varnish service restart
- Test thoroughly in a staging environment before deploying to production
- Consider using [elgentos/magento2-varnish-extended](https://github.com/elgentos/magento2-varnish-extended) for a more complete enhanced Varnish configuration
</details>

<details>
<summary><strong>READ MORE: Configuring Back/Forward cache with Fastly (including Adobe Commerce Cloud)</strong></summary>

For Fastly CDN, you must create two custom VCL snippets through the Magento admin panel, as follows:

**Step 1: Access VCL Snippets**
1. Navigate to **Stores** > **Settings** > **Configuration** > **Advanced** > **System**
2. Expand **Full Page Cache** > **Fastly Configuration** > **Custom VCL Snippets**
3. Click **Create Custom Snippet**

**Step 2: Configure Snippet 1**
- **Name**: `bfcache-preserve-public-private`
- **Type**: `fetch`
- **Priority**: `1`
- **VCL Content**:

```vcl
if (beresp.http.Cache-Control) {
    if (beresp.http.Cache-Control ~ "public") {
        set beresp.http.X-MageOS-Bfcache = "public";
    } else {
        set beresp.http.X-MageOS-Bfcache  = "private";
    }
}
```
Save the snippet  
Click **Create Custom Snippet** again

**Step 3: Configure Snippet 2**
- **Name**: `bfcache-remove-ccns`
- **Type**: `deliver`
- **Priority**: `100`
- **VCL Content**:

```vcl
if (fastly.ff.visits_this_service == 0 && req.restarts == 0) {
    if (resp.http.X-MageOS-Bfcache == "public") {
       set resp.http.Cache-Control = "no-cache, must-revalidate, max-age=0";
    }
}

unset resp.http.X-MageOS-Bfcache;
```
Save the snippet

**Step 4: Deploy**

Click **Upload VCL to Fastly**, and Activate the uploaded VCL
</details>

### Speculative Loading
* **Enable Speculation Rules** - Enables speculative loading to preload pages before links are clicked, making perceived load times faster. (Default: Yes)
* **Mode** - Choose between prefetch and prerender modes. (Default: prefetch)
  - Prefetch: Downloads resources in advance
  - Prerender: Fully renders pages in advance (faster but may affect analytics data and UI changes)
* **Eagerness Level** - Controls how aggressively pages are preloaded. (Default: Moderate)
  - Conservative: Minimal preloading, only when very likely to be needed
  - Moderate: Balanced approach between performance and resource usage
  - Eager: Aggressive preloading for maximum user experience, at the cost of loading pages the user may never visit
* **Exclude URL Patterns** - URL patterns to never preload. One pattern per line. (Default: customer, login, logout, auth, cart, checkout, search, download, redirect, rewrite, store, productalert)
  - URL patterns are matched against the request URI. We recommend entering part or full route paths, like "customer" (to exclude all customer pages) or "customer/account/logout" (to specifically exclude logout).
* **Exclude File Extensions** - File extensions to never preload. (Default: pdf, zip)
* **Exclude Selectors** - CSS selectors for links to never preload. Enter one selector per line. (Default: .do-not-prerender)

### View Transitions
* **Enable View Transitions** - Toggle animated page changes for storefront. (Default: Yes)
* **Enable View Transitions for Admin** - Enable transitions in Admin; disable if using a theme with built-in transitions. (Default: Yes)
* **Apply on Back/Forward** - Show transitions when using browser navigation; can be disabled for faster restores. (Default: Yes)

## Contributors

Initial module, page transitions, and speculation rules contributed by [@rhoerr](https://github.com/rhoerr).

Back/forward cache support was contributed by [Oli Jaufmann and @JaJuMa](https://github.com/JaJuMa).

Credit for the default speculation rules to [David Lambauer and @run_as_root](https://run-as-root.sh/blog/improving-pagespeed-with-speculative-loading).

This module is sponsored and maintained by [Mage-OS](https://mage-os.org). Mage-OS makes it open source and freely available for use by any Magento 2.4+ or Adobe Commerce website.
