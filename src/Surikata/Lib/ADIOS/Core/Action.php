<?php

namespace ADIOS\Core;

/**
 * Core implementation of ADIOS Action
 * 
 * 'Action' is fundamendal class for generating HTML content of each ADIOS call. Actions can
 * be rendered using Twig template or using custom render() method.
 * 
 */
class Action {
  /**
   * Reference to ADIOS object
   */
  protected $adios;
    
  /**
   * Shorthand for "global table prefix"
   */
  protected $gtp = "";
  
  /**
   * Array of parameters (arguments) passed to the action
   */
  protected $params;
  
  /**
   * Language dictionary for strings used in the action's output
   */
  public $languageDictionary;

  /**
   * If set to FALSE, the rendered content of action is available to public
   */
  public static $requiresUserAuthentication = TRUE;

  /**
   * If set to TRUE, the default ADIOS desktop will not be added to the rendered content
   */
  public static $hideDefaultDesktop = FALSE;

  /**
   * If set to FALSE, the action will not be rendered in CLI
   */
  public static $cliSAPIEnabled = TRUE;

  /**
   * If set to FALSE, the action will not be rendered in WEB
   */
  public static $webSAPIEnabled = TRUE;

  function __construct(&$adios, $params = []) {
    $this->adios = &$adios;
    $this->params = $params;
    $this->uid = $this->adios->uid;
    $this->gtp = $this->adios->gtp;
    $this->action = $this->adios->action;

    if (!is_array($this->params)) {
      $this->params = [];
    }

    $this->init();

  }

  public function init() {
    //
  }
  
  /**
   * Used to change ADIOS configuration before calling preRender()
   *
   * @param  array $config Current ADIOS configuration
   * @return array Changed ADIOS configuration
   */
  public static function overrideConfig($config) {
    return $config;
  }
  
  /**
   * Used to return values for TWIG renderer. Applies only in TWIG template of the action.
   *
   * @return array Values for action's TWIG template
   */
  public function preRender() {
    return [];
  }

  // public function applyURLParams($myParams) {
  //   if (empty($myParams) || !is_array($myParams)) $myParams = [];
  //   return array_merge($this->params['_GET'], $myParams);
  // }
  
  /**
   * Shorthand for ADIOS core translate() function. Uses own language dictionary.
   *
   * @param  string $string String to be translated
   * @param  string $context Context where the string is used
   * @param  string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, string $context = "", string $toLanguage = "") {
    return $this->adios->translate($string, $context, $toLanguage, $this->languageDictionary);
  }
  
  /**
   * Renders the content of requested action using Twig template.
   * In most cases is this method overriden.
   *
   * @return string Rendered HTML content of the action.
   * @return array Key-value pair of output values. Will be converted to JSON.
   * 
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\LoaderError
   */
  public function render() {
    $twigParams = array_merge($this->params, $this->preRender());

    $twigParams["uid"] = $this->adios->uid;
    $twigParams["gtp"] = $this->adios->gtp;
    $twigParams["config"] = $this->adios->config;
    $twigParams["user"] = $this->adios->userProfile;
    $twigParams["locale"] = $this->adios->locale->getAll();
    $twigParams['userNotifications'] = $this->adios->userNotifications->getAsHtml();

    try {
      return $this->adios->twig->render(
        $this->twigTemplate ?? str_replace("\\Actions\\", "\\Templates\\", static::class),
        $twigParams
      );
    } catch (\Twig\Error\RuntimeError $e) {
      throw ($e->getPrevious());
    } catch (\Twig\Error\LoaderError $e) {
      return $e->getMessage();
    }
  }
}

