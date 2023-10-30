<?php
/**
 * @package     Joomlaportal.Plugins
 * @subpackage  Content.JPJoomlaDownloads.Layouts
 * @author      Joomlaportal.ru Team <smart@joomlaportal.ru>
 * @copyright   Copyright (C) 2013-2023 Joomlaportal.ru. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

\defined('_JEXEC') or die;
?>
<?php if ($displayData['url'] && $displayData['version']) : ?>
	<a href="<?php echo $displayData['url']; ?>" target="_blank">Joomla <?php echo $displayData['version']; ?></a>
<?php endif; ?>
