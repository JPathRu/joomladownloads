<?php
/**
 * @package     Joomlaportal.Plugins
 * @subpackage  Content.JPJoomlaDownloads
 * @author      Joomlaportal.ru Team <smart@joomlaportal.ru>
 * @copyright   Copyright (C) 2013-2023 Joomlaportal.ru. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Content\Jpjoomladownloads\Extension\Jpjoomladownloads;

\defined('_JEXEC') or die;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.3.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin = new Jpjoomladownloads(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('content', 'jpjoomladownloads')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
