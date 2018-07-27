<?php
/**
 * Assets plugin for Craft CMS 3.x
 *
 * Utilities, eg asset()
 *
 * @link      https://hutsix.com.au
 * @copyright Copyright (c) 2018 HutSix
 */

namespace hut6\assets\twigextensions;

use club\assetrev\exceptions\ContinueException;
use Craft;
use Twig\TwigFunction;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    HutSix
 * @package   Assets
 * @since     0.1
 */
class AssetsTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    protected $webPath = 'web';

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'Assets';
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'getAsset'], ['is_safe' => ['html']]),
            new TwigFunction('embedSvg', [$this, 'inlineSvg'], ['is_safe' => ['html']]),
            new TwigFunction('embedSvgIcon', [$this, 'inlineSvgIcon'], ['is_safe' => ['html']]),
            new TwigFunction('has_manifest', [$this, 'hasAssetManifest']),
        ];
    }

    /**
     * @param string $file
     * @param bool $fullPath
     * @return string
     * @throws ContinueException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getAsset(string $file, $fullPath = false): string
    {

        $path = $this->getBasePath($file);

        $this->checkExists($file, $path);

        return $fullPath ? $this->getWebPath($file) : $this->normalise($file);
    }

    /**
     * @param string $file
     * @param string|null $class
     * @return string
     * @throws ContinueException
     */
    public function inlineSvg(string $file, string $class = null): string
    {
        $path = $this->getBasePath($file);

        $this->checkExists($file, $path);

        return sprintf('<span class="svg %s">%s</span>', $class, file_get_contents($path));
    }

    /**
     * @param string $file
     * @param string|null $class
     * @return string
     * @throws ContinueException
     */
    public function inlineSvgIcon(string $file, string $class = null): string
    {
        $path = $this->getBasePath($file);

        $this->checkExists($file, $path);

        return sprintf('<span class="icon svg-icon %s">%s</span>', $class, file_get_contents($path));
    }

    /**
     * In development check for existence of asset manifest
     *
     * @return bool
     */
    public function hasAssetManifest(): bool
    {
        $manifestFile = CRAFT_BASE_PATH . '/' . $this->webPath . '/assets/manifest.json';

        return file_exists($manifestFile);
    }

    /**
     * @param $file
     * @return string
     */
    private function getBasePath(string $file): string
    {
        return (string)str_replace('//', '/', CRAFT_BASE_PATH . '/' . $this->webPath . '/' . $file);
    }

    /**
     * @param $file
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    private function getWebPath(string $file): string
    {
        return Craft::$app->getSites()->getCurrentSite()->baseUrl . str_replace('//', '/', '/' . $file);
    }

    /**
     * @param $file
     * @param $path
     * @return mixed
     * @throws ContinueException
     */
    private function checkExists(string $file, string $path)
    {
        if (file_exists($path)) {
            return $path;
        }
        throw new ContinueException("Cannot find `$file`.");
    }

    /**
     * @param $file
     * @return string
     */
    private function normalise(string $file): string
    {
        if (strpos($file, '/') !== 0) {
            return '/' . $file;
        }

        return $file;
    }

}
