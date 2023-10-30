<?php
/**
 * @package     Joomlaportal.Plugins
 * @subpackage  Content.JPJoomlaDownloads
 * @author      Joomlaportal.ru Team <smart@joomlaportal.ru>
 * @copyright   Copyright (C) 2013-2023 Joomlaportal.ru. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */
 
namespace Joomla\Plugin\Content\Jpjoomladownloads\Extension;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;

\defined('_JEXEC') or die;

/**
 * JP JoomlaDownloads plugin class.
 *
 * @since 1.0
 */
final class Jpjoomladownloads extends CMSPlugin
{
	const PACKAGE_REGEXP = '#(\/cms\/joomla\d\/\d\-\d\-\d+\/Joomla_(\d\-\d+\-\d+)-Stable-Full_Package\.zip)#ism';
#	const RELEASE_DATE_REGEXP = '#(<relative-time datetime="([^"]+)")#ism';
	const RELEASE_DATE_REGEXP = '#(<local-time datetime="([^"]+)")#ism';
	const JOOMLA_DOWNLOAD_URL = 'https://downloads.joomla.org/';
	const JOOMLA_GITHUB_RELEASE_TAG_URL = 'https://github.com/joomla/joomla-cms/releases/tag/';

	/**
	 * Returns the list of Joomla versions.
	 *
	 * @param   string    $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row      An object with a "text" property
	 * @param   mixed     $params   Additional parameters. See {@see PlgContentContent()}.
	 * @param   ?int      $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentPrepare(string $context, mixed &$row, mixed &$params, ?int $page = 0): void
	{
		if ($context != 'com_content.article' && $context != 'com_content.category' && $context != 'mod_custom.content')
		{
			return;
		}

		$cache = Factory::getContainer()->get(
			CacheControllerFactoryInterface::class)->createCacheController('callback', ['defaultgroup' => 'plg_content_jpjoomladownloads']
		);
		$cache->setCaching(true);
		$cache->setLifeTime(3600 * $this->params->get('cachetimeout', 6));

		$data = $cache->get([$this, 'getJoomlaDownloadsData']);

		if (!empty($data))
		{
			$patterns     = [];
			$replacements = [];

			foreach ($data as $k => $v)
			{
				$patterns[]     = '#{' . $k . '}#i';
				$replacements[] = $v;
			}

			$row->text = preg_replace($patterns, $replacements, $row->text);
		}
	}

	/**
	 * Gets the download data.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getJoomlaDownloadsData(): array
	{
		$data = [];

		$versionInfo      = [];
		$versionInfo['3'] = ['version' => '3', 'url' => self::JOOMLA_DOWNLOAD_URL . '#latest', 'date' => ''];
		$versionInfo['4'] = ['version' => '4', 'url' => self::JOOMLA_DOWNLOAD_URL . '/latest', 'date' => ''];
		$versionInfo['5'] = ['version' => '5', 'url' => self::JOOMLA_DOWNLOAD_URL . '/latest', 'date' => ''];

		try
		{
			$http     = HttpFactory::getHttp();
			$response = $http->get(self::JOOMLA_DOWNLOAD_URL);

			if (200 == $response->code && !empty($response->body))
			{
				$m = [];
				preg_match_all(self::PACKAGE_REGEXP, $response->body, $m);

				for ($i = 0, $n = count($m[1]); $i < $n; $i++)
				{
					$url = 'https://downloads.joomla.org' . $m[1][$i];
					$version = str_replace('-', '.', $m[2][$i]);
					// $code               = ('2' === $version{0}) ? '25' : '3';
					$code               = '3';
					$versionInfo[$code] = ['version' => $version, 'url' => $url, 'date' => ''];

					$http     = HttpFactory::getHttp();
					$response = $http->get(self::JOOMLA_GITHUB_RELEASE_TAG_URL . $version);

					if (200 == $response->code && !empty($response->body))
					{
						$m = [];
						preg_match_all(self::RELEASE_DATE_REGEXP, $response->body, $m);

						if (isset($m[2]) && isset($m[2][0]))
						{
							$strDate = $m[2][0];

							if (strlen($strDate) > 10)
							{
								$strDate   = substr($strDate, 0, 10);
								$dateParts = preg_split('/\-/', $strDate);

								if (3 === count($dateParts))
								{
									$versionInfo[$code]['date'] = $dateParts[2] . '.' . $dateParts[1] . '.' . $dateParts[0];
								}
							}
						}
					}
				}
			}
		}
		catch (Exception $exception)
		{
			// do nothing (default values will be used)
		}

		foreach ($versionInfo as $k => $v)
		{
			$data['joomla' . $k . 'url']     = $v['url'];
			$data['joomla' . $k . 'version'] = $v['version'];
			$data['joomla' . $k . 'date']    = $v['date'];
			$data['joomla' . $k . 'link']    = self::getHtmlLink($v['version'], $v['url']);
		}

		return $data;
	}

	/**
	 * Gets the html link to Joomla version.
	 *
	 * @param   string  $version  The version number.
	 * @param   string  $url      URL to version.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private static function getHtmlLink($version, $url): string
	{
		LayoutHelper::$defaultBasePath = JPATH_PLUGINS . '/content/jpjoomladownloads/layouts';

		return LayoutHelper::render('link', ['version' => $version, 'url' => $url]);
	}
}
