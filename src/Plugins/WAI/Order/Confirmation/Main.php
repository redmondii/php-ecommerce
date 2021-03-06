<?php

namespace Surikata\Plugins\WAI\Order {
  class Confirmation extends \Surikata\Core\Web\Plugin {

    public function getWebPageUrlFormatted($urlVariables, $pluginSettings = []) {

      $order = $urlVariables["order"] ?? [];
      $orderModel = new \ADIOS\Widgets\Orders\Models\Order($this->adminPanel);

      $url = $pluginSettings["urlPattern"] ?? "";
      if (empty($url)) {
        $url = "order/{% orderNumber %}/{% checkCode %}/thank-you";
      }

      $url = str_replace("{% orderNumber %}", $order['number'], $url);
      $url = str_replace("{% checkCode %}", $orderModel->getCheckCode($order), $url);

      return $url;
    }

    public function getTwigParams($pluginSettings) {

      $twigParams = $pluginSettings;
      $orderNumber = (int) ($this->websiteRenderer->urlVariables["orderNumber"] ?? 0);
      $checkCode = $this->websiteRenderer->urlVariables["checkCode"] ?? "";

      $orderModel = new \ADIOS\Widgets\Orders\Models\Order($this->adminPanel);
      $order = $orderModel->getByNumber($orderNumber);

      if ($orderModel->validateCheckCode($order, $checkCode)) {
        $twigParams["order"] = $order;
      }

      return $twigParams;
    }

  }
}

namespace ADIOS\Plugins\WAI\Order {
  class Confirmation extends \Surikata\Core\AdminPanel\Plugin {

    var $defaultUrl = "order/{% orderNumber %}/{% checkCode %}/thank-you";

    public function getSiteMap($pluginSettings = [], $webPageUrl = "") {

      $urlPattern = $pluginSettings["urlPattern"] ?? "";
      if (empty($urlPattern)) {
        $urlPattern = $this->defaultUrl;
      }

      $this->convertUrlPatternToSiteMap(
        $siteMap,
        $urlPattern,
        [
          "orderNumber" => '(\d+)',
          "checkCode" => '(.*?)',
        ]
      );

      return $siteMap;
    }

    public function getSettingsForWebsite() {
      return [
        "urlPattern" => [
          "title" => "Order confirmation page URL",
          "type" => "varchar",
          "description" => "
            Relative URL for order confirmation page.<br/>
            Default value: order/{% orderNumber %}/{% checkCode %}/thank-you
          ",
        ],
      ];
    }
  }
}