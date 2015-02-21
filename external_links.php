<?php
/**
 * External Links v1.2.0
 *
 * This plugin adds small icons to external and mailto links, informing
 * users the link will take them to a new site or open their email client.
 *
 * Licensed under MIT, see LICENSE.
 *
 * @package     External Links
 * @version     1.2.0
 * @link        <https://github.com/sommerregen/grav-plugin-external-links>
 * @author      Benjamin Regler <sommerregen@benjamin-regler.de>
 * @copyright   2015, Benjamin Regler
 * @license     <http://opensource.org/licenses/MIT>            MIT
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
class ExternalLinksPlugin extends Plugin {
  /**
   * @var ExternaLinksPlugin
   */

  /**
   * Instance of ExternalLinks class
   *
   * @var object
   */
  protected $external_links;

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
  public static function getSubscribedEvents() {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0],
    ];
  }

  /**
   * Initialize configuration.
   */
  public function onPluginsInitialized() {
    if ($this->isAdmin()) {
      $this->active = false;
      return;
    }

    if ( $this->config->get('plugins.external_links.enabled') ) {
      // Initialize ExternalLinks class
      require_once(__DIR__ . '/classes/ExternalLinks.php');
      $this->external_links = new ExternalLinks();

      // Process contents order according to weight option
      $weight = $this->config->get('plugins.external_links.weight');

      $this->enable([
        'onPageContentProcessed' => ['onPageContentProcessed', $weight],
        'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
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
  public function onPageContentProcessed(Event $event) {
    /** @var Page $page */
    $page = $event['page'];

    $config = $this->mergeConfig($page);
    if ( $config->get('process', FALSE) AND $this->compileOnce($page) ) {
      // Do nothing, if a route for a given page does not exist
      if ( !$page->route() ) {
        return;
      }

      // Check if mode option is valid
      $mode = $config->get('mode', 'passive');
      if ( !in_array($mode, array('active', 'passive')) ) {
        return;
      }

      // Get content and list of exclude tags
      $content = $page->getRawContent();

      // Apply external links filter and save modified page content
      $page->setRawContent(
        $this->external_links->process($content, $config)
      );
    }
  }

  /**
   * Set needed variables to display external links.
   */
  public function onTwigSiteVariables() {
    if ( $this->config->get('plugins.external_links.built_in_css') ) {
      $this->grav['assets']->add('plugin://external_links/assets/css/external_links.css');
    }
  }

  /** -------------------------------
   * Private/protected helper methods
   * --------------------------------
   */

  /**
   * Checks if a page has already been compiled yet.
   *
   * @param  Page    $page The page to check
   * @return boolean       Returns TRUE if page has already been
   *                       compiled yet, FALSE otherwise
   */
  protected function compileOnce(Page $page) {
    static $processed = array();

    $id = md5($page->path());
    // Make sure that contents is only processed once
    if ( !isset($processed[$id]) OR ($processed[$id] < $page->modified()) ) {
      $processed[$id] = $page->modified();
      return TRUE;
    }

    return FALSE;
  }
}
