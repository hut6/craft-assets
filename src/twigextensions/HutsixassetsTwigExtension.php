<?php
/**
 * Hutsixassets plugin for Craft CMS 3.x
 *
 * Utilities, eg asset()
 *
 * @link      https://hutsix.com.au
 * @copyright Copyright (c) 2018 HutSix
 */

namespace hut6\hutsixassets\twigextensions;

use Craft;
use Twig\TwigFunction;

/**
 * @author    HutSix
 * @package   Hutsixassets
 * @since     0.1
 */
class HutsixassetsTwigExtension extends \Twig_Extension
{
    /**
     * @var string
     */
    protected $webPath = 'web';

    /**
     * @var array
     */
    protected $decodedManifest = [];

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'Hutsixassets';
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'getAsset'], ['is_safe' => ['html']]),
            new TwigFunction('asset_exists', [$this, 'assetExists']),
            new TwigFunction('embedSvg', [$this, 'inlineSvg'], ['is_safe' => ['html']]),
            new TwigFunction('embedSvgIcon', [$this, 'inlineSvgIcon'], ['is_safe' => ['html']]),
            new TwigFunction('has_manifest', [$this, 'manifestExists']),
        ];
    }

    /**
     * @param string $file
     * @param bool $fullPath
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getAsset(string $file, bool $absolute = false): string
    {
        $path = $this->getActualPath($file);

        if ($this->isUrl($path)) {
            return $path;
        }

        return $this->getWebPath($path, $absolute);
    }

    /**
     * @param $file
     * @return string
     */
    private function getActualPath(string $file): string
    {
        $path = $this->toAbsolutePath($file);

        if (file_exists($path)) {
            return $path;
        }

        if ($this->manifestExists()) {
            $data = $this->getManifestData();
            $key = ltrim($file, '/');
            if (array_key_exists($key, $data)) {
                return $this->toAbsolutePath($data[$key]);
            }
        }

        if ($this->isUrl($file)) {
            $file_headers = @get_headers($file);
            if ($file_headers[0] !== 'HTTP/1.1 404 Not Found') {
                return $file;
            }
        }
    }

    /**
     * @param $file
     * @return mixed
     */
    private function toAbsolutePath($file)
    {
        if ($this->isUrl($file)) {
            return $file;
        }

        return str_replace('//', '/', CRAFT_BASE_PATH.'/'.$this->webPath.'/'.$file);
    }

    /**
     * @param string $file
     * @return mixed
     */
    private function isUrl(string $file)
    {
        return filter_var($file, FILTER_VALIDATE_URL);
    }

    /**
     * In development check for existence of asset manifest
     *
     * @return bool
     */
    public function manifestExists(): bool
    {
        return file_exists($this->getManifestPath());
    }

    /**
     * @return mixed
     */
    private function getManifestPath()
    {
        return $this->toAbsolutePath('/assets/manifest.json');
    }

    /**
     * In development check for existence of asset manifest
     *
     * @return bool
     */
    public function getManifestData(): array
    {
        if(!$this->decodedManifest) {
            $this->decodedManifest = json_decode(file_get_contents($this->getManifestPath()), true);
        }

        return $this->decodedManifest;
    }

    /**
     * @param $path
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    private function getWebPath(string $path, bool $absolute = false): string
    {
        $path = $this->toRelativePath($path);

        if ($absolute) {
            return Craft::$app->getSites()->getCurrentSite()->baseUrl.$path;
        }

        return $path;
    }

    /**
     * @param $file
     * @return mixed
     */
    private function toRelativePath($file)
    {
        return str_replace(CRAFT_BASE_PATH.'/'.$this->webPath.'/', '/', $file);
    }

    /**
     * @param string $file
     * @param bool $fullPath
     * @return string
     * @throws ContinueException
     * @throws \craft\errors\SiteNotFoundException
     */
    public function assetExists(string $file): string
    {
        $path = $this->getActualPath($file);

        if ($this->isUrl($path)) {
            $file_headers = @get_headers($path);

            return $file_headers[0] == 'HTTP/1.1 404 Not Found';
        }

        return file_exists($path);
    }

    /**
     * @param string $file
     * @param string|null $class
     * @return string
     * @throws ContinueException
     */
    public function inlineSvgIcon(string $file, string $class = null): string
    {
        return $this->inlineSvg($file, "svg-icon ".$class);
    }

    /**
     * @param string $file
     * @param string|null $class
     * @return string
     * @throws ContinueException
     */
    public function inlineSvg(string $file, string $class = null): string
    {
        return sprintf(
            '<span class="svg %s">%s</span>',
            $class,
            $this->file_get_contents($this->getActualPath($file))
        );
    }

    /**
     * @param string $file
     * @return bool|false|string
     */
    private function file_get_contents(string $file)
    {
        if ($this->isUrl($file)) {

            $client = new \GuzzleHttp\Client();

            $res = $client->get(
                $file,
                [
                    'curl' => [CURLOPT_SSL_VERIFYPEER => false],
                    'verify',
                    false,
                ]
            );

            return $res->getBody()->getContents();
        }

        return file_get_contents($file);
    }

}
