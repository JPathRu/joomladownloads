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
use Joomla\CMS\HTML\HTMLHelper;
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
	const JOOMLA_DOWNLOAD_URL = 'https://downloads.joomla.org/cms';
	const JOOMLA_GITHUB_RELEASES_TAGS_URL = 'https://api.github.com/repos/joomla/joomla-cms/releases/tags';
	const JOOMLA_API_LATEST_URL = 'https://downloads.joomla.org/api/v1/latest/cms';

	/**
	 * Returns the list of Joomla versions.
	 *
	 * @param   string    $context  The context of the content being passed to the plugin.
	 * @param   object    $row      An object with a "text" property
	 * @param   mixed     $params   Additional parameters. See {@see PlgContentContent()}.
	 * @param   ?int      $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentPrepare(string $context, object $row, $params, ?int $page = 0): void
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

		try
		{
			$http = (new HttpFactory)->getHttp();

			$response = $http->get(self::JOOMLA_API_LATEST_URL);
			$body     = $response->body;

			if ($response->code != 200 || empty($body))
			{
				return $data;
			}

			$branches = json_decode($response->body, true);
			$lastTwo  = array_reverse(array_slice($branches['branches'], -2));

			$i = 1;

			foreach ($lastTwo as $branch)
			{
				$response = $http->get(self::JOOMLA_GITHUB_RELEASES_TAGS_URL . '/' . $branch['version']);
				$body     = $response->body;

				if ($response->code != 200 || empty($body))
				{
					continue;
				}

				$tagData = json_decode($body);

				$data['latest' . $i . 'name'] = $tagData->name;
				$data['latest' . $i . 'date'] = HTMLHelper::date($tagData->datepublished_at, 'd.m.Y');
				$data['latest' . $i . 'version'] = $tagData->tag_name;
				$versionUrl = str_replace('.', '-', $tagData->tag_name);
				$data['latest' . $i . 'install'] = self::JOOMLA_DOWNLOAD_URL . '/joomla' . substr($tagData->tag_name, 0, 1)
					. '/' . $versionUrl . '/Joomla_' . $versionUrl . '-Stable-Full_Package.zip?format=zip';
				$data['latest' . $i . 'update'] = self::JOOMLA_DOWNLOAD_URL . '/joomla' . substr($tagData->tag_name, 0, 1)
					. '/' . $versionUrl . '/Joomla_' . $versionUrl . '-Stable-Update_Package.zip?format=zip';
				$data['latest' . $i . 'linkinstall'] = self::getHtmlLink($tagData->tag_name, $data['latest' . $i . 'install']);
				$data['latest' . $i . 'linkupdate'] = self::getHtmlLink($tagData->tag_name, $data['latest' . $i. 'update']);

				$i++;
			}

			if (empty($data))
			{
				return $data;
			}
		}
		catch (\Exception $e)
		{
			// do nothing (default values will be used)
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
	private static function getHtmlLink(string $version, string $url): string
	{
		LayoutHelper::$defaultBasePath = JPATH_PLUGINS . '/content/jpjoomladownloads/layouts';

		return LayoutHelper::render('link', ['version' => $version, 'url' => $url]);
	}
}
