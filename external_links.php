<?php
/**
 * External Links v1.3.0
 *
 * This plugin adds small icons to external and mailto links, informing
 * users the link will take them to a new site or open their email client.
 *
 * Dual licensed under the MIT or GPL Version 3 licenses, see LICENSE.
 * http://benjamin-regler.de/license/
 *
 * @package     External Links
 * @version     1.3.0
 * @link        <https://github.com/sommerregen/grav-plugin-external-links>
 * @author      Benjamin Regler <sommerregen@benjamin-regler.de>
 * @copyright   2015, Benjamin Regler
 * @license     <http://opensource.org/licenses/MIT>        MIT
 * @license     <http://opensource.org/licenses/GPL-3.0>    GPLv3
 */

namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

/**
 * External Links Plugin
 *
 * This plugin adds small icons to external and mailto links, informing
 * users the link will take them to a new site or open their email client.
 */
class ExternalLinksPlugin extends Plugin
{
  /**
   * @var ExternaLinksPlugin
   */

  /**
   * Instance of ExternalLinks class
   *
   * @var \Grav\Plugin\ExternalLinks
   */
  protected $backend;

  /** -------------
   * Public methods
   * --------------
   */

  /**
   * Return a list of subscribed events.
   *
   * @return array    The list of events of the plugin of the form
   *                      'name' => ['method_name', priority].
   */
  public static function getSubscribedEvents()
  {
    return [
      'onTwigInitialized' => ['onTwigInitialized', 0],
      'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
      'onBuildPagesInitialized' => ['onBuildPagesInitialized', 0]
    ];
  }

  /**
   * Initialize configuration when building pages.
   */
  public function onBuildPagesInitialized()
  {
    if ($this->isAdmin()) {
      $this->active = false;
      return;
    }

    if ($this->config->get('plugins.external_links.enabled')) {
      $this->init();

      // Process contents order according to weight option
      $weight = $this->config->get('plugins.external_links.weight');

      $this->enable([
        'onPageContentProcessed' => ['onPageContentProcessed', $weight]
      ]);
    }
  }

  /**
   * Apply external links filter to content, when each page has not been
   * cached yet.
   *
   * @param  Event  $event The event when 'onPageContentProcessed' was
   *                       fired.
   */
  public function onPageContentProcessed(Event $event)
  {
    /** @var Page $page */
    $page = $event['page'];

    $config = $this->mergeConfig($page);
    if ($config->get('process', false) && $this->compileOnce($page)) {
      // Do nothing, if a route for a given page does not exist
      if (!$page->route()) {
        return;
      }

      // Check if mode option is valid
      $mode = $config->get('mode', 'passive');
      if (!in_array($mode, array('active', 'passive'))) {
        return;
      }

      // Get content and list of exclude tags
      $content = $page->getRawContent();

      // Apply external links filter and save modified page content
      $page->setRawContent(
        $this->backend->process($content, $config)
      );
    }
  }

  /**
   * Initialize Twig configuration and filters.
   */
  public function onTwigInitialized()
  {
    // Expose function
    $this->grav['twig']->twig()->addFunction(
      new \Twig_SimpleFunction('external_links', [$this, 'externalLinksFunction'], ['is_safe' => ['html']])
    );
  }

  /**
   * Set needed variables to display external links.
   */
  public function onTwigSiteVariables()
  {
    if ($this->config->get('plugins.external_links.built_in_css')) {
      $this->grav['assets']->add('plugin://external_links/assets/css/external_links.css');
    }
  }

  /**
   * Filter to parse external links.
   *
   * @param  string $content The content to be filtered.
   * @param  array  $options Array of options for the External links filter.
   *
   * @return string          The filtered content.
   */
  public function externalLinksFunction($content, $params = [])
  {
    $config = $this->mergeConfig($this->grav['page'], $params);
    return $this->init()->process($content, $config);
  }

  /** -------------------------------
   * Private/protected helper methods
   * --------------------------------
   */

  /**
   * Checks if a page has already been compiled yet.
   *
   * @param  Page    $page The page to check
   * @return boolean       Returns true if page has already been
   *                       compiled yet, false otherwise
   */
  protected function compileOnce(Page $page)
  {
    static $processed = [];

    $id = md5($page->path());
    // Make sure that contents is only processed once
    if (!isset($processed[$id]) || ($processed[$id] < $page->modified())) {
      $processed[$id] = $page->modified();
      return true;
    }

    return false;
  }

  /**
   * Initialize plugin and all dependencies.
   *
   * @return \Grav\Plugin\ExternalLinks   Returns ExternalLinks instance.
   */
  protected function init()
  {
    if (!$this->backend) {
      // Initialize back-end
      require_once(__DIR__ . '/classes/ExternalLinks.php');
      $this->backend = new ExternalLinks();
    }

    return $this->backend;
  }
}
