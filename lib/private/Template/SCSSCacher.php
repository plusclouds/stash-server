<?php
/**
 * @copyright Copyright (c) 2016, John Molakvoæ (skjnldsv@protonmail.com)
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roland Tapken <roland@bitarbeiter.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Template;

use OC\AppConfig;
use OC\Files\AppData\Factory;
use OC\Memcache\NullCache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IMemcache;
use OCP\IURLGenerator;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\ParserException;
use ScssPhp\ScssPhp\OutputStyle;

class SCSSCacher {

	/** @var ILogger */
	protected $logger;

	/** @var IAppData */
	protected $appData;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IConfig */
	protected $config;

	/** @var \OC_Defaults */
	private $defaults;

	/** @var string */
	protected $serverRoot;

	/** @var ICache */
	protected $depsCache;

	/** @var null|string */
	private $injectedVariables;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var IconsCacher */
	private $iconsCacher;

	/** @var ICache */
	private $isCachedCache;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IMemcache */
	private $lockingCache;
	/** @var AppConfig */
	private $appConfig;

	/**
	 * @param ILogger $logger
	 * @param Factory $appDataFactory
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 * @param \OC_Defaults $defaults
	 * @param string $serverRoot
	 * @param ICacheFactory $cacheFactory
	 * @param IconsCacher $iconsCacher
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(ILogger $logger,
								Factory $appDataFactory,
								IURLGenerator $urlGenerator,
								IConfig $config,
								\OC_Defaults $defaults,
								$serverRoot,
								ICacheFactory $cacheFactory,
								IconsCacher $iconsCacher,
								ITimeFactory $timeFactory,
								AppConfig $appConfig) {
<<<<<<< HEAD
		$this->logger = $logger;
		$this->appData = $appDataFactory->get('css');
=======
		$this->logger       = $logger;
		$this->appData      = $appDataFactory->get('css');
>>>>>>> stable20
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->defaults = $defaults;
		$this->serverRoot = $serverRoot;
		$this->cacheFactory = $cacheFactory;
		$this->depsCache = $cacheFactory->createDistributed('SCSS-deps-' . md5($this->urlGenerator->getBaseUrl()));
		$this->isCachedCache = $cacheFactory->createDistributed('SCSS-cached-' . md5($this->urlGenerator->getBaseUrl()));
		$lockingCache = $cacheFactory->createDistributed('SCSS-locks-' . md5($this->urlGenerator->getBaseUrl()));
		if (!($lockingCache instanceof IMemcache)) {
			$lockingCache = new NullCache();
		}
		$this->lockingCache = $lockingCache;
		$this->iconsCacher = $iconsCacher;
		$this->timeFactory = $timeFactory;
		$this->appConfig = $appConfig;
	}

	/**
	 * Process the caching process if needed
	 *
	 * @param string $root Root path to the nextcloud installation
	 * @param string $file
	 * @param string $app The app name
	 * @return boolean
	 * @throws NotPermittedException
	 */
	public function process(string $root, string $file, string $app): bool {
		$path = explode('/', $root . '/' . $file);

		$fileNameSCSS = array_pop($path);
		$fileNameCSS = $this->prependVersionPrefix($this->prependBaseurlPrefix(str_replace('.scss', '.css', $fileNameSCSS)), $app);

		$path = implode('/', $path);
		$webDir = $this->getWebDir($path, $app, $this->serverRoot, \OC::$WEBROOT);

		$this->logger->debug('SCSSCacher::process ordinary check follows', ['app' => 'scss_cacher']);
		if (!$this->variablesChanged() && $this->isCached($fileNameCSS, $app)) {
			// Inject icons vars css if any
			return $this->injectCssVariablesIfAny();
		}

		try {
			$folder = $this->appData->getFolder($app);
		} catch (NotFoundException $e) {
			// creating css appdata folder
			$folder = $this->appData->newFolder($app);
		}

		$lockKey = $webDir . '/' . $fileNameSCSS;

		if (!$this->lockingCache->add($lockKey, 'locked!', 120)) {
			$this->logger->debug('SCSSCacher::process could not get lock for ' . $lockKey . ' and will wait 10 seconds for cached file to be available', ['app' => 'scss_cacher']);
			$retry = 0;
			sleep(1);
			while ($retry < 10) {
				$this->appConfig->clearCachedConfig();
				$this->logger->debug('SCSSCacher::process check in while loop follows', ['app' => 'scss_cacher']);
				if (!$this->variablesChanged() && $this->isCached($fileNameCSS, $app)) {
					// Inject icons vars css if any
					$this->logger->debug("SCSSCacher::process cached file for app '$app' and file '$fileNameCSS' is now available after $retry s. Moving on...", ['app' => 'scss_cacher']);
					return $this->injectCssVariablesIfAny();
				}
				sleep(1);
				$retry++;
			}
			$this->logger->debug('SCSSCacher::process Giving up scss caching for ' . $lockKey, ['app' => 'scss_cacher']);
			return false;
		}

		$this->logger->debug('SCSSCacher::process Lock acquired for ' . $lockKey, ['app' => 'scss_cacher']);
		try {
			$cached = $this->cache($path, $fileNameCSS, $fileNameSCSS, $folder, $webDir);
		} catch (\Exception $e) {
			$this->lockingCache->remove($lockKey);
			throw $e;
		}

		// Cleaning lock
		$this->lockingCache->remove($lockKey);
		$this->logger->debug('SCSSCacher::process Lock removed for ' . $lockKey, ['app' => 'scss_cacher']);

		// Inject icons vars css if any
		if ($this->iconsCacher->getCachedCSS() && $this->iconsCacher->getCachedCSS()->getSize() > 0) {
			$this->iconsCacher->injectCss();
		}

		return $cached;
	}

	/**
	 * @param $appName
	 * @param $fileName
	 * @return ISimpleFile
	 */
	public function getCachedCSS(string $appName, string $fileName): ISimpleFile {
		$folder = $this->appData->getFolder($appName);
		$cachedFileName = $this->prependVersionPrefix($this->prependBaseurlPrefix($fileName), $appName);

		return $folder->getFile($cachedFileName);
	}

	/**
	 * Check if the file is cached or not
	 * @param string $fileNameCSS
	 * @param string $app
	 * @return boolean
	 */
	private function isCached(string $fileNameCSS, string $app) {
		$key = $this->config->getSystemValue('version') . '/' . $app . '/' . $fileNameCSS;

		// If the file mtime is more recent than our cached one,
		// let's consider the file is properly cached
		if ($cacheValue = $this->isCachedCache->get($key)) {
			if ($cacheValue > $this->timeFactory->getTime()) {
				return true;
			}
		}
		$this->logger->debug("SCSSCacher::isCached $fileNameCSS isCachedCache is expired or unset", ['app' => 'scss_cacher']);

		// Creating file cache if none for further checks
		try {
			$folder = $this->appData->getFolder($app);
		} catch (NotFoundException $e) {
			$this->logger->debug("SCSSCacher::isCached app data folder for $app could not be fetched", ['app' => 'scss_cacher']);
			return false;
		}

		// Checking if file size is coherent
		// and if one of the css dependency changed
		try {
			$cachedFile = $folder->getFile($fileNameCSS);
			if ($cachedFile->getSize() > 0) {
				$depFileName = $fileNameCSS . '.deps';
				$deps = $this->depsCache->get($folder->getName() . '-' . $depFileName);
				if ($deps === null) {
					$depFile = $folder->getFile($depFileName);
					$deps = $depFile->getContent();
					// Set to memcache for next run
					$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
				}
				$deps = json_decode($deps, true);

				foreach ((array) $deps as $file => $mtime) {
					if (!file_exists($file) || filemtime($file) > $mtime) {
						$this->logger->debug("SCSSCacher::isCached $fileNameCSS is not considered as cached due to deps file $file", ['app' => 'scss_cacher']);
						return false;
					}
				}

				$this->logger->debug("SCSSCacher::isCached $fileNameCSS dependencies successfully cached for 5 minutes", ['app' => 'scss_cacher']);
				// It would probably make sense to adjust this timeout to something higher and see if that has some effect then
				$this->isCachedCache->set($key, $this->timeFactory->getTime() + 5 * 60);
				return true;
			}
			$this->logger->debug("SCSSCacher::isCached $fileNameCSS is not considered as cached cacheValue: $cacheValue", ['app' => 'scss_cacher']);
			return false;
		} catch (NotFoundException $e) {
			$this->logger->debug("SCSSCacher::isCached NotFoundException " . $e->getMessage(), ['app' => 'scss_cacher']);
			return false;
		}
	}

	/**
	 * Check if the variables file has changed
	 * @return bool
	 */
	private function variablesChanged(): bool {
<<<<<<< HEAD
		$cachedVariables = $this->config->getAppValue('core', 'theming.variables', '');
		$injectedVariables = $this->getInjectedVariables($cachedVariables);
		if ($cachedVariables !== md5($injectedVariables)) {
=======
		$injectedVariables = $this->getInjectedVariables();
		if ($this->config->getAppValue('core', 'theming.variables') !== md5($injectedVariables)) {
>>>>>>> stable20
			$this->logger->debug('SCSSCacher::variablesChanged storedVariables: ' . json_encode($this->config->getAppValue('core', 'theming.variables')) . ' currentInjectedVariables: ' . json_encode($injectedVariables), ['app' => 'scss_cacher']);
			$this->config->setAppValue('core', 'theming.variables', md5($injectedVariables));
			$this->resetCache();
			return true;
		}
		return false;
	}

	/**
	 * Cache the file with AppData
	 *
	 * @param string $path
	 * @param string $fileNameCSS
	 * @param string $fileNameSCSS
	 * @param ISimpleFolder $folder
	 * @param string $webDir
	 * @return boolean
	 * @throws NotPermittedException
	 */
	private function cache(string $path, string $fileNameCSS, string $fileNameSCSS, ISimpleFolder $folder, string $webDir) {
		$scss = new Compiler();
		$scss->setImportPaths([
			$path,
			$this->serverRoot . '/core/css/'
		]);

		// Continue after throw
		if ($this->config->getSystemValue('debug')) {
			// Debug mode
			$scss->setOutputStyle(OutputStyle::EXPANDED);
		} else {
			// Compression
			$scss->setOutputStyle(OutputStyle::COMPRESSED);
		}

		try {
			$cachedfile = $folder->getFile($fileNameCSS);
		} catch (NotFoundException $e) {
			$cachedfile = $folder->newFile($fileNameCSS);
		}

		$depFileName = $fileNameCSS . '.deps';
		try {
			$depFile = $folder->getFile($depFileName);
		} catch (NotFoundException $e) {
			$depFile = $folder->newFile($depFileName);
		}

		// Compile
		try {
			$compiledScss = $scss->compile(
				'$webroot: \'' . $this->getRoutePrefix() . '\';' .
				$this->getInjectedVariables() .
				'@import "variables.scss";' .
				'@import "functions.scss";' .
				'@import "' . $fileNameSCSS . '";');
		} catch (ParserException $e) {
			$this->logger->logException($e, ['app' => 'scss_cacher']);

			return false;
		}

		// Parse Icons and create related css variables
		$compiledScss = $this->iconsCacher->setIconsCss($compiledScss);

		// Gzip file
		try {
			$gzipFile = $folder->getFile($fileNameCSS . '.gzip'); # Safari doesn't like .gz
		} catch (NotFoundException $e) {
			$gzipFile = $folder->newFile($fileNameCSS . '.gzip'); # Safari doesn't like .gz
		}

		try {
			$data = $this->rebaseUrls($compiledScss, $webDir);
			$cachedfile->putContent($data);
			$deps = json_encode($scss->getParsedFiles());
			$depFile->putContent($deps);
			$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
			$gzipFile->putContent(gzencode($data, 9));
			$this->logger->debug('SCSSCacher::cache ' . $webDir . '/' . $fileNameSCSS . ' compiled and successfully cached', ['app' => 'scss_cacher']);

			return true;
		} catch (NotPermittedException $e) {
			$this->logger->error('SCSSCacher::cache unable to cache: ' . $fileNameSCSS, ['app' => 'scss_cacher']);

			return false;
		}
	}

	/**
	 * Reset scss cache by deleting all generated css files
	 * We need to regenerate all files when variables change
	 */
	public function resetCache() {
		$this->logger->debug('SCSSCacher::resetCache', ['app' => 'scss_cacher']);
		if (!$this->lockingCache->add('resetCache', 'locked!', 120)) {
			$this->logger->debug('SCSSCacher::resetCache Locked', ['app' => 'scss_cacher']);
			return;
		}
		$this->logger->debug('SCSSCacher::resetCache Lock acquired', ['app' => 'scss_cacher']);
		$this->injectedVariables = null;

		// do not clear locks
		$this->depsCache->clear();
		$this->isCachedCache->clear();

		$appDirectory = $this->appData->getDirectoryListing();
		foreach ($appDirectory as $folder) {
			foreach ($folder->getDirectoryListing() as $file) {
				try {
					$file->delete();
				} catch (NotPermittedException $e) {
					$this->logger->logException($e, ['message' => 'SCSSCacher::resetCache unable to delete file: ' . $file->getName(), 'app' => 'scss_cacher']);
				}
			}
		}
		$this->logger->debug('SCSSCacher::resetCache css cache cleared!', ['app' => 'scss_cacher']);
		$this->lockingCache->remove('resetCache');
		$this->logger->debug('SCSSCacher::resetCache Locking removed', ['app' => 'scss_cacher']);
	}

	/**
	 * @return string SCSS code for variables from OC_Defaults
	 */
	private function getInjectedVariables(string $cache = ''): string {
		if ($this->injectedVariables !== null) {
			return $this->injectedVariables;
		}
		$variables = '';
		foreach ($this->defaults->getScssVariables() as $key => $value) {
			$variables .= '$' . $key . ': ' . $value . ' !default;';
		}

		/*
		 * If we are trying to return the same variables as that are cached
		 * Then there is no need to do the compile step
		 */
		if ($cache === md5($variables)) {
			$this->injectedVariables = $variables;
			return $variables;
		}

		// check for valid variables / otherwise fall back to defaults
		try {
			$scss = new Compiler();
			$scss->compile($variables);
			$this->injectedVariables = $variables;
		} catch (ParserException $e) {
			$this->logger->logException($e, ['app' => 'scss_cacher']);
		}

		return $variables;
	}

	/**
	 * Add the correct uri prefix to make uri valid again
	 * @param string $css
	 * @param string $webDir
	 * @return string
	 */
	private function rebaseUrls(string $css, string $webDir): string {
		$re = '/url\([\'"]([^\/][\.\w?=\/-]*)[\'"]\)/x';
		$subst = 'url(\'' . $webDir . '/$1\')';

		return preg_replace($re, $subst, $css);
	}

	/**
	 * Return the cached css file uri
	 * @param string $appName the app name
	 * @param string $fileName
	 * @return string
	 */
	public function getCachedSCSS(string $appName, string $fileName): string {
		$tmpfileLoc = explode('/', $fileName);
		$fileName = array_pop($tmpfileLoc);
		$fileName = $this->prependVersionPrefix($this->prependBaseurlPrefix(str_replace('.scss', '.css', $fileName)), $appName);

		return substr($this->urlGenerator->linkToRoute('core.Css.getCss', [
			'fileName' => $fileName,
			'appName' => $appName,
			'v' => $this->config->getAppValue('core', 'theming.variables', '0')
		]), \strlen(\OC::$WEBROOT) + 1);
	}

	/**
	 * Prepend hashed base url to the css file
	 * @param string $cssFile
	 * @return string
	 */
	private function prependBaseurlPrefix(string $cssFile): string {
		return substr(md5($this->urlGenerator->getBaseUrl() . $this->getRoutePrefix()), 0, 4) . '-' . $cssFile;
	}

	private function getRoutePrefix() {
		$frontControllerActive = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		$prefix = \OC::$WEBROOT . '/index.php';
		if ($frontControllerActive) {
			$prefix = \OC::$WEBROOT;
		}
		return $prefix;
	}

	/**
	 * Prepend hashed app version hash
	 * @param string $cssFile
	 * @param string $appId
	 * @return string
	 */
	private function prependVersionPrefix(string $cssFile, string $appId): string {
		$appVersion = \OC_App::getAppVersion($appId);
		if ($appVersion !== '0') {
			return substr(md5($appVersion), 0, 4) . '-' . $cssFile;
		}
		$coreVersion = \OC_Util::getVersionString();

		return substr(md5($coreVersion), 0, 4) . '-' . $cssFile;
	}

	/**
	 * Get WebDir root
	 * @param string $path the css file path
	 * @param string $appName the app name
	 * @param string $serverRoot the server root path
	 * @param string $webRoot the nextcloud installation root path
	 * @return string the webDir
	 */
	private function getWebDir(string $path, string $appName, string $serverRoot, string $webRoot): string {
		// Detect if path is within server root AND if path is within an app path
		if (strpos($path, $serverRoot) === false && $appWebPath = \OC_App::getAppWebPath($appName)) {
			// Get the file path within the app directory
			$appDirectoryPath = explode($appName, $path)[1];
			// Remove the webroot

			return str_replace($webRoot, '', $appWebPath . $appDirectoryPath);
		}

		return $webRoot . substr($path, strlen($serverRoot));
	}

	/**
	 * Add the icons css cache in the header if needed
	 *
	 * @return boolean true
	 */
	private function injectCssVariablesIfAny() {
		// Inject icons vars css if any
		if ($this->iconsCacher->getCachedCSS() && $this->iconsCacher->getCachedCSS()->getSize() > 0) {
			$this->iconsCacher->injectCss();
		}
		return true;
	}
}
