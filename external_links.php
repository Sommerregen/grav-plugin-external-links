<?php
/**
 * External Links v1.1.3
 *
 * This plugin adds small icons to external and mailto links, informing
 * users the link will take them to a new site or open their email client.
 *
 * Licensed under MIT, see LICENSE.
 *
 * @package     External Links
 * @version     1.1.3
 * @link        <https://github.com/sommerregen/grav-plugin-archive-plus>
 * @author      Benjamin Regler <sommergen@benjamin-regler.de>
 * @copyright   2015, Benjamin Regler
 * @license     <http://opensource.org/licenses/MIT>            MIT
 */

namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Utils;
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
      $weight = $this->config->get('plugins.external_links.weight');
      // Process contents order according to weight option

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

    // Modify page content
    if ( $config->get('process', TRUE) ) {
      $content = $page->getRawContent();

      /**
       * Two Really good resources to handle DOMDocument with HTML(5)
       * correctly.
       *
       * @see http://stackoverflow.com/questions/3577641/how-do-you-parse-and-process-html-xml-in-php
       * @see http://stackoverflow.com/questions/7997936/how-do-you-format-dom-structures-in-php
       */

      // Clear previous errors
      if ( libxml_use_internal_errors(TRUE) === TRUE ) {
        libxml_clear_errors();
      }

      // Create a DOM parser object
      $dom = new \DOMDocument('1.0', 'UTF-8');

      // Pretty print output
      $dom->preserveWhiteSpace = FALSE;
      $dom->formatOutput       = TRUE;

      // Normalize newlines
      $content = preg_replace('~\R~u', "\n", $content);

      // Parse the HTML using UTF-8
      // The @ before the method call suppresses any warnings that
      // loadHTML might throw because of invalid HTML in the page.
      @$dom->loadHTML($content);

      // Do nothing, if DOM is empty or a route for a given page
      // does not exist
      if ( is_null($dom->documentElement) OR !$page->route() ) {
        return;
      }

      $links = $dom->getElementsByTagName('a');
      foreach ( $links as $a ) {
        // Process links with non-empty href attribute only
        $href = $a->getAttribute('href');
        if ( strlen($href) == 0 ) {
          continue;
        }

        // Get the class of the <a> element
        $class = $a->hasAttribute('class') ? $a->getAttribute('class') : '';
        $classes = array_filter(explode(' ', $class));

        $exclude = $config->get('exclude.classes');
        if ( $exclude AND in_array($exclude, $classes) ) {
          continue;
        }

        // This is a mailto link.
        if ( strpos($href, 'mailto:') === 0 ) {
          $classes[] = 'mailto';
        }

        // The link is external
        elseif ( $this->isExternalUrl($href) ) {
          // Add external class
          $classes[] = 'external-link';

          // Add target="_blank"
          $target = $config->get('target');
          if ( $target ) {
            $a->setAttribute('target', $target);
          }

          // Add no-follow.
          $nofollow = $config->get('no_follow');
          if ( $nofollow ) {
            $rel = array_filter(explode(' ', $a->getAttribute('rel')));
            if ( !in_array('nofollow', $rel) ) {
              $rel[] = 'nofollow';
              $a->setAttribute('rel', implode(' ', $rel));
            }
          }

          // Add image class to <a> if it has at least one <img> child element
          $imgs = $a->getElementsByTagName('img');
          if ( $imgs->length > 1 ) {
            // Add "images" class to <a> element, if it has multiple child images
            $classes[] = 'images';
          } elseif ( $imgs->length == 1 ) {
            $imgNode = $imgs->item(0);

            // Get image size
            list($width, $height) = $this->getImageSize($imgNode);

            // Determine maximum dimension of image size
            $size = max($width, $height);

            // Depending on size determine image type
            $classes[] = ( (0 < $size) AND ($size <= 32) ) ? 'icon' : 'image';
          } else {
            // Add "no-image" class to <a> element, if it has no child images
            $classes[] = 'no-image';
          }

          // Add title (aka alert text) e.g.
          //    This link will take you to an external web site.
          //    We are not responsible for their content.
          // $title = $this->config->get('plugins.external_links.title');
          // $a->setAttribute('data-title', $title);
        }

        // Set class attribute
        if ( count($classes) ) {
          $a->setAttribute('class', implode(' ', $classes));
        }
      }

      $content = '';
      // Transform DOM document to valid HTML(5)
      $body = $dom->getElementsByTagName('body')->item(0);
      foreach ( $body->childNodes as $node ) {
        // Expand empty tags (e.g. <br/> to <br></br>)
        if ( ($html = $dom->saveXML($node, LIBXML_NOEMPTYTAG)) !== FALSE ) {
          $content .= $html;
        }
      }

      // Fix formatting for self-closing tags in HTML5 and removing
      // encapsulated (uncommented) CDATA blocks in <script> and
      // <style> tags
      $regex = array(
        '~' . preg_quote('<![CDATA[', '~') . '~' => '',
        '~' . preg_quote(']]>', '~') . '~' => '',
        '~></(?:area|base(?:font)?|br|col|command|embed|frame|hr|img|input|keygen|link|meta|param|source|track|wbr)>~' => ' />',
      );

      // Make XML HTML5 compliant
      $content = preg_replace(array_keys($regex), $regex, $content);

      // Write content back to page
      $page->setRawcontent($content);
    }
  }

  /**
   * Set needed variables to display external links.
   */
  public function onTwigSiteVariables() {
    if ( $this->config->get('plugins.external_links.built_in_css') ) {
      $this->grav['assets']->add('plugin://external_links/css/external_links.css');
    }
  }

  /** -------------------------------
   * Private/protected helper methods
   * --------------------------------
   */

  /**
   * Test if a URL is external
   *
   * @param  string  $url The URL to test.
   * @return boolean      Returns TRUE, if the URL is external, FALSE
   *                      otherwise.
   */
  protected function isExternalUrl($url) {
    static $allowed_protocols;
    static $pattern;

    // Statically store allowed protocols
    if ( !isset($allowed_protocols) ) {
      $allowed_protocols = array_flip(array(
        'ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp',
        'rtsp', 'sftp', 'ssh', 'tel', 'telnet', 'webcal')
      );
    }

    // Statically store internal domains as a PCRE pattern.
    if ( !isset($pattern) ) {
      $domains = array();
      $urls = (array) $this->config->get('plugins.external_links.exclude.domains');
      $urls = array_merge($urls, array($this->grav['base_url_absolute']));

      foreach ( $urls as $domain ) {
        $domains[] = preg_quote($domain, '#');
      }
      $pattern = '#(' . str_replace(array('\*', '/*'), '.*?', implode('|', $domains)) . ')#i';
    }

    $external = FALSE;
    if ( !preg_match($pattern, $url) ) {
      // Check if URL is external by extracting colon position
      $colonpos = strpos($url, ':');
      if ( $colonpos > 0 ) {
        // We found a colon, possibly a protocol. Verify.
        $protocol = strtolower(substr($url, 0, $colonpos));
        if ( isset($allowed_protocols[$protocol]) ) {
          // The protocol turns out be an allowed protocol
          $external = TRUE;
        }
      } elseif ( Utils::startsWith($url, 'www.') ) {
        // We found an url without protocol, but with starting
        // 'www' (sub-)domain
        $external = TRUE;
      }
    }

    // Only if a colon and a valid protocol was found return TRUE
    return ($colonpos !== FALSE) AND $external;
  }

  /**
   * Determine the size of an image
   *
   * @param  DOMNode $imgNode The image already parsed as a DOMNode
   * @param  integer $limit   Load first $limit KB of remote image
   * @return array            Return the dimension of the image of the
   *                          format array(width, height)
   */
  protected function getImageSize($imgNode, $limit = 32) {
    // Hold units (assume standard font with 16px base pixel size)
    // Calculations based on pixels
    $units = array(
      'px' => 1,            /* base unit: pixel */
      'pt' => 16 / 12,      /* 12 point = 16 pixel = 1/72 inch */
      'pc' => 16,           /* 1 pica = 16 pixel = 12 points */

      'in' => 96,           /* 1 inch = 96 pixel = 2.54 centimeters */
      'mm' => 96 / 25.4,    /* 1 millimeter = 96 pixel / 1 inch [mm] */
      'cm' => 96 / 2.54,    /* 1 centimeter = 96 pixel / 1 inch [cm] */
      'm' => 96 / 0.0254,   /* 1 centimeter = 96 pixel / 1 inch [m] */

      'ex' => 7,            /* 1 ex = 7 pixel */
      'em' => 16,           /* 1 em = 16 pixel */
      'rem' => 16,          /* 1 ex = 16 pixel */

      '%' => 16 / 100,      /* 100 percent = 16 pixel */
    );

    // Initialize dimensions
    $width = 0;
    $height = 0;

    // Determine image dimensions based on "src" atrribute
    if ( $imgNode->hasAttribute('src') ) {
      $src = $imgNode->getAttribute('src');

      // Simple check if the URL is internal i.e. check if path exists
      $path = $_SERVER['DOCUMENT_ROOT'] . $src;
      if ( realpath($path) AND is_file($path) ) {
        $size = @getimagesize($path);
      } else {
        // The URL is external; try to load it (default: 32 KB)
        $size = $this->getRemoteImageSize($src, $limit * 1024);
      }
    }

    // Read out width and height from <img> attributes
    $width = $imgNode->hasAttribute('width') ?
      $imgNode->getAttribute('width')  : $size[0];
    $height = $imgNode->hasAttribute('height') ?
      $imgNode->getAttribute('height')  : $size[1];

    // Get width and height from style attribute
    if ( $imgNode->hasAttribute('style') ) {
      $style = $imgNode->getAttribute('style');

      // Width
      if ( preg_match('~width:\s*(\d+)([a-z]+)~i', $style, $matches) ) {
        $width = $matches[1];
        // Convert unit to pixel
        if ( isset($units[$matches[2]]) ) {
          $width *= $units[$matches[2]];
        }
      }

      // Height
      if ( preg_match('~height:\s*(\d+)([a-z]+)~i', $style, $matches) ) {
        $height = $matches[1];
        // Convert unit to pixel
        if ( isset($units[$matches[2]]) ) {
          $height *= $units[$matches[2]];
        }
      }
    }

    // Update width and height
    $size[0] = $width;
    $size[1] = $height;

    // Return image dimensions
    return $size;
  }

  /**
   * Get the size of a remote image
   *
   * @param  string  $uri   The URI of the remote image
   * @param  integer $limit Load first $limit bytes of remote image
   * @return mixed          Returns an array with up to 7 elements
   */
  protected function getRemoteImageSize($uri, $limit = -1) {
    // Create temporary file to store data from $uri
    $tmp_name = tempnam(sys_get_temp_dir(), uniqid('ris'));
    if ( $tmp_name === FALSE ) {
      return FALSE;
    }

    // Open temporary file
    $tmp = fopen($tmp_name, 'rb');

    // Check which method we should use to get remote image sizes
    $allow_url_fopen = ini_get('allow_url_fopen') ? TRUE : FALSE;
    $use_curl = function_exists('curl_version');

    // Use stream copy
    if ( $allow_url_fopen ) {
      $options = array();
      if ( $limit > 0 ) {
        // Loading number of $limit bytes
        $options['http']['header'] = array('Range: bytes=0-' . $limit);
      }

      // Create stream context
      $context = stream_context_create($options);
      @copy($uri, $tmp_name, $context);

    // Use Curl
    } elseif ( $use_curl ) {
      // Initialize Curl
      $options = array(
        CURLOPT_HEADER => FALSE,            // Don't return headers
        CURLOPT_FOLLOWLOCATION => TRUE,     // Follow redirects
        CURLOPT_AUTOREFERER => TRUE,        // Set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // Timeout on connect
        CURLOPT_TIMEOUT => 120,             // Timeout on response
        CURLOPT_MAXREDIRS => 10,            // Stop after 10 redirects
        CURLOPT_ENCODING => '',             // Handle all encodings
        CURLOPT_BINARYTRANSFER => TRUE,     // Transfer as binary file
        CURLOPT_FILE => $tmp,               // Curl file
        CURLOPT_URL => $uri,                // URI
      );

      $curl = curl_init();
      curl_setopt_array($curl, $options);

      if ( $limit > 0 ) {
        // Loading number of $limit
        $headers = array('Range: bytes=0-' . $limit);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RANGE, '0-' . $limit);

        // Abort request when more data is received
        curl_setopt($curl, CURLOPT_BUFFERSIZE, 512);    // More progress info
        curl_setopt($curl, CURLOPT_NOPROGRESS, FALSE);  // Monitor progress
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION,
          function($download_size, $downloaded, $upload_size, $uploaded) use ($limit) {
            // If $downloaded exceeds $limit, returning non-zero breaks
            // the connection!
            return ( $downloaded > $limit ) ? 1 : 0;
        });
      }

      // Execute Curl
      curl_exec($curl);
      curl_close($curl);
    }

    // Close temporary file
    fclose($tmp);

    // Retrieve image information
    $info = array(0, 0, 'width="0" height="0"');
    if ( filesize($tmp_name) > 0 ) {
      $info = @getimagesize($tmp_name);
    }

    // Delete temporary file
    unlink($tmp_name);

    return $info;
  }
}
