<?php
/**
 * @package     Joomlaportal.Plugins
 * @subpackage  Content.JPJoomlaDownloads
 *
 * @author      Joomlaportal.ru Team <smart@joomlaportal.ru>
 * @copyright   Copyright (C) 2013-2015 Joomlaportal.ru. All rights reserved.
 * @license     GNU General Public License version 3 or later; see license.txt
 */
defined('_JEXEC') or die;

/**
 * JP JoomlaDownloads plugin class.
 *
 * @package     Joomlaportal.Plugins
 * @subpackage  Content.JPJoomlaDownloads
 */
class plgContentJpjoomladownloads extends JPlugin
{
	const PACKAGE_REGEXP = '#(https\:\/\/github\.com\/joomla\/joomla-cms\/releases\/download\/\d\.\d\.\d+\/Joomla_(\d\.\d+\.\d+)-Stable-Full_Package\.zip)#ism';
	const RELEASE_DATE_REGEXP = '#(<time datetime="([^"]+)" is="relative-time">)#ism';
	const JOOMLA_DOWNLOAD_URL = 'http://www.joomla.org/download.html';
	const JOOMLA_GITHUB_RELEASE_TAG_URL = 'https://github.com/joomla/joomla-cms/releases/tag/';

	/**
	 * Method to replace tags with data
	 *
	 * @param   string $context  The context of the content being passed to the plugin
	 * @param   mixed  &$article The article object
	 * @param   array  &$params  The article params
	 * @param   int    $page     The page number
	 */
	function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if ($context != 'com_content.article' && $context != 'com_content.category' && $context != 'mod_custom.content')
		{
			return;
		}

		$cache_timeout = $this->params->get('cachetimeout', 6, 'int');

		$cache = JFactory::getCache('plg_content_jpjoomladownloads', 'callback');
		$cache->setCaching(true);
#		$cache->setCaching(false);
		$cache->setLifeTime(3600 * $cache_timeout);

		$data = $cache->get(array($this, 'getJoomlaDownloadsData'));

		if (!empty($data))
		{
			$patterns     = array();
			$replacements = array();
			foreach ($data as $k => $v)
			{
				$patterns[]     = '#{' . $k . '}#i';
				$replacements[] = $v;
			}

			$article->text = preg_replace($patterns, $replacements, $article->text);
		}
	}

	public function getJoomlaDownloadsData()
	{
		$data = array();

		$versionInfo      = array();
		$versionInfo['3'] = array('version' => '3', 'url' => self::JOOMLA_DOWNLOAD_URL . '#latest', 'date' => '');

		try
		{
			$http     = JHttpFactory::getHttp();
			$response = $http->get(self::JOOMLA_DOWNLOAD_URL);

			if (200 == $response->code && !empty($response->body))
			{
				$m = array();
				preg_match_all(self::PACKAGE_REGEXP, $response->body, $m);
				for ($i = 0, $n = count($m[1]); $i < $n; $i++)
				{
					$url                = $m[1][$i];
					$version            = $m[2][$i];
					$code               = '2' === $version{0} ? '25' : '3';
					$versionInfo[$code] = array('version' => $version, 'url' => $url, 'date' => '');

					$http     = JHttpFactory::getHttp();
					$response = $http->get(self::JOOMLA_GITHUB_RELEASE_TAG_URL . $version);

					if (200 == $response->code && !empty($response->body))
					{
						$m = array();
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

	private static function getHtmlLink($version, $url)
	{
		$displayData            = array();
		$displayData['version'] = $version;
		$displayData['url']     = $url;

		JLayoutHelper::$defaultBasePath = JPATH_PLUGINS . '/content/jpjoomladownloads/layouts';

		return JLayoutHelper::render('link', $displayData);
	}
}