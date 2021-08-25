<?php

namespace Surikata\Plugins\WAI\Product {
  use ADIOS\Widgets\Products\Models\Service;
  class Detail extends \Surikata\Core\Web\Plugin {
    var $productInfo = NULL;

    public function getWebPageUrlFormatted($urlVariables, $pluginSettings = []) {
      $languageIndex = (int) ($this->websiteRenderer->domain["languageIndex"] ?? 1);

      $productName = $urlVariables["name_lang_{$languageIndex}"] ?? "";
      $idProduct = (int) $urlVariables["id"] ?? 0;
      return \ADIOS\Core\HelperFunctions::str2url($productName).".pid.{$idProduct}";
    }

    function getProductInfo() {
      if ($this->productInfo === NULL) {
        $this->productInfo = $this->adminPanel
          ->getModel("Widgets/Products/Models/Product")
          ->getById((int) $this->websiteRenderer->urlVariables['idProduct'])
        ;

        $allCategories = (new \ADIOS\Widgets\Products\Models\ProductCategory($this->adminPanel))->getAll(); // TODO: UPPERCASE LOOKUP

        foreach ($this->productInfo['prislusenstvo'] as $key => $value) {
          $this->productInfo['prislusenstvo'][$key]['url'] =
            \ADIOS\Core\HelperFunctions::str2url($value['name_lang_1'])
            .".pid.{$value['id']}"
          ;
        }

        foreach ($this->productInfo['podobne'] as $key => $value) {
          $this->productInfo['podobne'][$key]['url'] =
            \ADIOS\Core\HelperFunctions::str2url($value['name_lang_1'])
            .".pid.{$value['id']}"
          ;
        }

        $this->productInfo['priceInfo'] = $this->adminPanel
          ->getModel("Widgets/Products/Models/Product")
          ->getPriceInfoForSingleProduct((int) $this->websiteRenderer->urlVariables['idProduct'])
        ;

        $this->productInfo['breadcrumbs'] = $this->adminPanel
          ->getModel("Widgets/Products/Models/ProductCategory")
          ->breadcrumbs((int) $this->productInfo['id_category'], $allCategories)
        ;
      }

      $allUnits = (new \ADIOS\Widgets\Settings\Models\Unit($this->adminPanel))->getAll();
      foreach ($allUnits as $unit) {
        if ($this->productInfo["id_delivery_unit"] == $unit["id"]) {
          $this->productInfo["DELIVERY_UNIT"] = $unit;
          break;
        }
      }
      foreach ($this->productInfo['FEATURES'] as $key => $feature) {
        foreach ($allUnits as $unit) {
          if ($feature["id_measurement_unit"] == $unit["id"]) {
            $this->productInfo['FEATURES'][$key]["MEASUREMENT_UNIT"] = $unit;
            break;
          }
        }
      }
      return $this->productInfo;
    }

    public function getServices() {

      /** @var Service $serviceModel */
      $serviceModel = $this->adminPanel
        ->getModel("Widgets/Products/Models/Service")
      ;

      /** @var array $services */
      $services = $serviceModel
        ->getAll()
      ;
      return $services;
    }
    
    public function renderJSON() {
      $returnArray = [];
      $productAction = $this->websiteRenderer->urlVariables['productAction'] ?? "";

      switch ($productAction) {
        case "getQuickView":
          $product = $this->getProductInfo();
          $returnArray["product"] = [];
          $returnArray["product"] = $product;
          $returnArray["productModalContent"] = (new \Surikata\Plugins\WAI\Product\Detail\Modals\ProductModal($this->websiteRenderer))
            ->renderDefaultModal($product)
          ;
          break;
      }

      return $returnArray;
    }

    public function getTwigParams($pluginSettings) {

      $customerUID = $this->websiteRenderer->getCustomerUID();
      $idProduct = (int) $this->websiteRenderer->urlVariables['idProduct'];

      // save datetime of render
      //$this->adminPanel
     //   ->getModel("Widgets/Customers/Models/CustomerProduktPrezerany")
     //   ->logActivityByCustomerUID($customerUID, $idProduct)
     // ;

      $twigParams = $pluginSettings;

      $twigParams["services"] = $this->getServices();
      $twigParams["productInfo"] = (new \Surikata\Plugins\WAI\Product\Detail($this->websiteRenderer))->getProductInfo();

      return $twigParams;
    }
  }
}

namespace ADIOS\Plugins\WAI\Product {
  class Detail extends \Surikata\Core\AdminPanel\Plugin {

    public function getSiteMap($pluginSettings = [], $webPageUrl = "") {
      return [
        $webPageUrl . '(.+).pid.(\d+)' => [
          1 => "name",
          2 => "idProduct",
        ],
      ];
    }

    public function getSettingsForWebsite() {
      return [
        "show_accessories" => [
          "title" => "Show accessories for products",
          "type" => "boolean",
        ],
        "zobrazit_podobne_produkty" => [
          "title" => "Zobraziť podobné produkty",
          "type" => "boolean",
        ],
      ];
    }

    public function onModelAfterFormParams($event) {
      return $event;
    }
  }
}