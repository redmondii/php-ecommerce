<?php

namespace Surikata\Core\Web;

use \voku\helper\HtmlMin;

/**
 * Loader class for the Surikata engine. Encapsulates CASCADA for website presentation and ADIOS for administration
 */
class Loader extends \Cascada\Loader {

  /** Reference to ADIOS object. Enables API of the administration panel. */
  var $adminPanel;

  /** List of already loaded and created content plugins for the website presentation */
  var $pluginObjects = [];

  /** Stores the name of the theme chosen in the administration panel */
  var $themeName = "";

  /** Stores the path to the folder with Theme's files */
  var $themeDir = "";

  var $pluginsDir = "";

  var $paymentPlugins = [];
  var $deliveryPlugins = [];

  var $userLogged = NULL;

  var $pages;
  var $currentPage;

  var $domain;


  var $controllers;
  var $currentRenderedPlugin = NULL;

  var $translationCache = NULL;

  /**
   * Class constructor.
   * 
   * Does following:
   *   - create the ADIOS object into the $surikata property
   *   - load list of published sites (pages) from the database
   *     (uses Widgets/Website/Models/WebPage model)
   *   - loads the settings of the website from the database
   *     (uses Core/Models/Config model)
   *   - sets $themeName and $themeDir properties
   *   - initalizes CASCADA's Twig and introduces own Twig functions 'callSurikataMethod' and 'callPluginMethod'
   * 
   * @param array $config Configuration for the Surikata engine.
   * 
   */
  public function __construct($config, $adminPanel = NULL) {

    $this->adminPanel = $adminPanel;

    if (is_object($this->adminPanel)) {
      $this->adminPanel->websiteRenderer = $this;
    }

    // parent::__construct
    parent::__construct($config);

    $this->pluginsDir = $config['pluginsDir'];

    if (is_object($this->adminPanel)) {

      // Ak nie je nastaveny adminPanel, tak websiteRenderer
      // nema odkial vediet, aka tema a aka stranka sa ma zobrazovat.
      // Neinicializuju sa teda properties potrebne pre renderovanie webu.

      $this->pages = $this->loadPublishedPages();
      $this->currentPage = NULL;

      $this->adminPanel->webSettings = $this->loadSurikataSettings("web/{$this->config["domainToRender"]}");

      $this->themeName = $this->adminPanel->webSettings["design"]["theme"];
      $this->themeDir = "{$this->adminPanel->config['themes_dir']}/{$this->themeName}";

      $this->assetsUrlMap["core/assets/"] = ADMIN_PANEL_SRC_DIR."/Core/Assets/";
      $this->assetsUrlMap["theme/assets/"] = "{$this->themeDir}/Assets/";
      $this->assetsUrlMap["plugins/assets/"] = PLUGINS_DIR."/";
      $this->assetsUrlMap["upload/image/resize/"] = function($websiteRenderer, $template) { 
        $template = str_replace("upload/image/resize/", "", $template);
        preg_match('/(\d+)\/(\d+)\/(.+)/', $template, $m);

        $requestedWidth = (int) $m[1];
        $requestedHeight = (int) $m[2];
        $fileName = urldecode($m[3]);

        $img = new \Surikata\Core\Web\ImageProcessor("{$websiteRenderer->adminPanel->config['files_dir']}/{$fileName}");

        if ($requestedWidth > 0 && $requestedHeight > 0) {
          $img->resize($requestedWidth, $requestedHeight);
        } elseif ($requestedWidth > 0) {
          $img->resizeToWidth($requestedWidth);
        } elseif ($requestedHeight > 0) {
          $img->resizeToWidth($requestedHeight);
        }

        $cachingTime = 3600;
        $headerExpires = "Expires: ".gmdate("D, d M Y H:i:s", time() + $cachingTime) . " GMT";
        $headerCacheControl = "Cache-Control: max-age={$cachingTime}";

        header($headerExpires);
        header("Pragma: cache");
        header($headerCacheControl);

        switch ($img->imageType) {
          case IMAGETYPE_JPEG: header('Content-Type: image/jpeg'); break;
          case IMAGETYPE_GIF: header('Content-Type: image/gif'); break;
          case IMAGETYPE_PNG: header('Content-Type: image/png'); break;
        }

        $img->output();

        exit();
      };

      $this->domain = $this->adminPanel->config['widgets']['Website']['domains'][$this->config["domainToRender"]];

      $this->initTwig();

      // priklad volania v Twig sablone:
      //   {{ callSurikataMethod('methodName', [param1, param2]) }}
      // nasledne bude zavolana metoda: $___CASCADAObject->methodName($param1, $param2)
      $this->twig->addFunction(new \Twig\TwigFunction(
        'callSurikataMethod',
        function ($function, $params = []) {
          global $___CASCADAObject;
          return call_user_func_array(
            [$___CASCADAObject, $function],
            [$params]
          );
        }
      ));

      // podobny princip, ako callSurikataMethod, akurat sa vola metoda pluginu
      // NETESTOVANE
      $this->twig->addFunction(new \Twig\TwigFunction(
        'callPluginMethod',
        function ($pluginName, $function, $params = []) {
          global $___CASCADAObject;
          return call_user_func_array(
            [$___CASCADAObject->getPlugin($pluginName), $function],
            [$params]
          );
        }
      ));

      $this->twig->addFunction(new \Twig\TwigFunction(
        'translate',
        function ($str, $context = NULL) {
          global $___CASCADAObject;

          $domain = $___CASCADAObject->config['domainToRender'];
          $translationModel = new \ADIOS\Widgets\Settings\Models\Translation($this->adminPanel);

          if (
            $context === NULL
            && $___CASCADAObject->currentRenderedPlugin !== NULL
          ) {
            $context = $___CASCADAObject->currentRenderedPlugin->name;
          }

          $context = (string) $context;

          if ($___CASCADAObject->translationCache === NULL) {

            $allTranslations = $translationModel->get()->toArray();

            foreach ($allTranslations as $translation) {
              $___CASCADAObject->translationCache
                [$translation["original"]]
                [$translation["context"]] = json_decode($translation["translated"], true);
            }

          }

          if (empty($___CASCADAObject->translationCache[$str][$context])) {
            $translationModel->insertRow([
              "context" => $context,
              "original" => $str,
              "translated" => "",
            ]);

            $translatedText = $str;
          } else {
            $translatedText = $___CASCADAObject->translationCache[$str][$context][$domain];
          }

          return $translatedText;
        }
      ));

      $this->setGlobal();
      $this->setRouter(new \Cascada\Router($this->getSiteMap()));
    }

  }

  public function render() {
    try {
      parent::render();

      $outputFormat = ($_GET['__output'] ?? "");

      if ($outputFormat != "json" && $this->config['minifyOutputHtml'] ?? FALSE) {
        $htmlMinifier = new HtmlMin();
        return $htmlMinifier->minify($this->outputHtml);
      } else {
        return $this->outputHtml;
      }
    } catch (
      \Illuminate\Database\QueryException
      | \ADIOS\Core\DBException
      $e
    ) {
      $errorHash = md5(date("YmdHis").$e->getMessage());
      $this->adminPanel->console->log($errorHash, $e->getMessage());
      return json_encode([
        "status" => "FAIL",
        "exception" => "SurikataCore",
        "error" => "Oops! Something went wrong with the database. See logs for more information. Error hash: {$errorHash}",
      ]);
    }
  }

  /**
   * Loads the site map for CASCADA router.
   * 
   * @return array Site map definition for CASCADA router.
   * */
  public function getSiteMap() {
    $siteMap = [
      // controllers pouzite pri vsetkych URL
      "*" => [
        "controllers" => [
          new \Surikata\Core\Web\Controllers\UserProfile($this),
          new \Surikata\Core\Web\Controllers\General($this),
        ],
      ],

      // 404
      "NotFoundTemplate" => "404",

    ];

    $siteMapDomain = (new \ADIOS\Widgets\Website($this->adminPanel))->loadSitemapForDomain($this->config["domainToRender"]);

    $siteMap = array_merge($siteMap, $siteMapDomain);

    $siteMap = $this->adminPanel->dispatchEventToPlugins("onAfterSiteMap", [
      "site_map" => $siteMap,
      "website_renderer" => $this,
    ])["site_map"];

    if (!is_array($siteMap)) $siteMap = [];

    return $siteMap;

  }

  public function onGeneralControllerAfterRouting() {
    // to be overriden
  }

  /**
   * Loads settings of the website configured by the user in the administration panel.
   * 
   * @param string group Name of the settings group.
   * 
   * @return array Website settings configured in the administration panel.
   * */
  public function loadSurikataSettings($group) {
    $path = "settings/{$group}/";

    $tmp = (new \ADIOS\Core\Models\Config($this->adminPanel))
      ->where('path', 'like', "{$path}%")
      ->get()
      ->toArray()
    ;

    $settings = [];
    foreach ($tmp as $value) {
      $tmp_path = str_replace($path, "", $value['path']);
      list($tmp_level_1, $tmp_level_2) = explode("/", $tmp_path);
      if (empty($tmp_level_2)) {
        $settings[$tmp_level_1] = $value['value'];
      } else {
        $settings[$tmp_level_1][$tmp_level_2] = $value['value'];
      }
    }
    
    return $settings;
  }

  /**
   * Loads the list of published sites of the website managed by the user in the administration panel.
   * 
   * @return array List of published sites.
   * */
  public function loadPublishedPages() {
    $tmp = (new \ADIOS\Widgets\Website\Models\WebPage($this->adminPanel))
      ->where('publish_always', '1')
      ->orWhere(function($q) {
        $q
          ->where('publish_from', '<=', date("Y-m-d"))
          ->where('publish_to', '>=', date("Y-m-d"))
        ;
      })
      ->get()
      ->toArray()
    ;

    $pages = [];
    foreach ($tmp as $value) {
      $pages[$value['id']] = $value;
    }

    return $pages;
  }

  /**
   * Returns the object of the content plugin.
   * 
   * First checks if the object has already been created. If yes, simply returns it.
   * If not, creates it, stores it into the $plugins property and returns.
   * 
   * @param string pluginName Name of the plugin.
   * 
   * @return object Of class \Surikata\Plugin.
   */
  public function getPlugin($pluginName) {
    if (empty($this->pluginObjects[$pluginName])) {
      $pluginClassName = "\\Surikata\\Plugins\\".str_replace("/", "\\", $pluginName);
      $this->pluginObjects[$pluginName] = new $pluginClassName($this);
    }

    return $this->pluginObjects[$pluginName];
  }

  public function getCurrentPagePluginSettings($pluginName, $panelName = "") {
    $pluginSettings = NULL;

    $contentStructure = @json_decode(($this->currentPage['content_structure'] ?? ""), TRUE);
    if (is_array($contentStructure)) {
      foreach ($contentStructure['panels'] as $tmpPanelName => $panelSettings) {
        if (!empty($panelName) && $tmpPanelName != $panelName) continue;
        if (!empty($panelSettings["plugin"]) && $panelSettings["plugin"] == $pluginName) {
          $pluginSettings = $panelSettings["settings"] ?? [];
          break;
        }
      }

    }

    return $pluginSettings;
  }

  /**
   * Returns unique identifier of the customer / visitor of the website.
   * 
   * Not implemented yet. Returns 'CustUID'.
   * 
   * @return string Unique identifier of the customer
   * */
  public function getCustomerUID() {
    $cookieName = 'srkt-c-uid';

    if (empty($_COOKIE[$cookieName])) {
      $customerUID = uniqid(md5(time()).".", TRUE);
      setcookie($cookieName, $customerUID, time() + 3600 * 24 * 30);  /* expire in 1 month */
    } else {
      $customerUID = $_COOKIE[$cookieName];
    }
    
    return $customerUID;
  }

  public function registerPaymentPlugin($pluginName) {
    $pluginClassName = "\\Surikata\\Plugins\\{$pluginName}";
    if (
      !in_array($pluginName, $this->paymentPlugins)
      && property_exists($pluginClassName, 'isPaymentPlugin')
      && $pluginClassName::$isPaymentPlugin ?? FALSE
    ) {
      $this->paymentPlugins[$pluginName] = $this->getPlugin($pluginName);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getPaymentPlugins() {
    $plugins = scandir($this->pluginsDir);
    foreach ($plugins as $pluginName) {
      if (!in_array($pluginName, [".", ".."])) {
        $this->registerPaymentPlugin($pluginName);
      }
    }
    return $this->paymentPlugins;
  }

  public function registerDeliveryPlugin($pluginName) {
    $pluginClassName = "\\Surikata\\Plugins\\".str_replace("/", "\\", $pluginName);

    if (
      !in_array($pluginName, $this->deliveryPlugins)
      && property_exists($pluginClassName, 'isDeliveryPlugin')
      && $pluginClassName::$isDeliveryPlugin ?? FALSE
    ) {
      $this->deliveryPlugins[$pluginName] = $this->getPlugin($pluginName);
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getDeliveryPlugins() {
    foreach ($this->adminPanel->plugins as $pluginName) {
      if (!in_array($pluginName, [".", ".."])) {
        $this->registerDeliveryPlugin($pluginName);
      }
    }
    return $this->deliveryPlugins;
  }

}
