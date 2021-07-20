<?php

namespace ADIOS\Widgets\Products\Models;

class ProductExtension extends \ADIOS\Core\Model {
  var $sqlName = "products_extensions";
  var $urlBase = "Products/{{ id_product }}/Extensions";
  var $tableTitle = "Product extensions";
  var $formTitleForInserting = "New product extension";
  var $formTitleForEditing = "Product extensions";

  public function columns(array $columns = []) {
    $translatedColumns = [];
    $domainLanguages = $this->adios->config['widgets']['Website']['domainLanguages'];

    foreach ($domainLanguages as $languageIndex => $languageName) {
      $translatedColumns["name_lang_{$languageIndex}"] = [
        "type" => "varchar",
        "title" => $this->translate("Name")." ({$languageName})",
        "show_column" => ($languageIndex == 1),
        "is_searchable" => ($languageIndex == 1),
      ];
      $translatedColumns["description_lang_{$languageIndex}"] = [
        "type" => "text",
        "title" => $this->translate("Description")." ({$languageName})",
        "interface" => "formatted_text",
        "show_column" => FALSE,
        "is_searchable" => ($languageIndex == 1),
      ];
    }

    return parent::columns(array_merge(
      $translatedColumns,
      [
        "id_product" => [
          "type" => "lookup",
          "model" => "Widgets/Products/Models/Product",
          "title" => "Product",
          "readonly" => TRUE,
          "show_column" => FALSE,
        ],

        "description" => [
          "type" => "text",
          "title" => $this->translate("Description"),
        ],

        "sale_price" => [
          "type" => "float",
          "title" => $this->translate("Price"),
          "unit" => $this->adios->locale->currencySymbol(),
          "show_column" => TRUE,
        ],

        "image" => [
          "type" => "image",
          "title" => "Image",
          "show_column" => TRUE,
          "subdir" => "products"
        ],
      ]
    ));
  }

  public function tableParams($params) {
    $params["where"] = "`{$this->table}`.`id_product` = ".(int) $params['id_product'];
    $params['show_search_button'] = FALSE;
    $params['show_controls'] = FALSE;
    $params['show_filter'] = FALSE;
    $params['title'] = " ";

    return $params;
  }

  public function formParams($data, $params) {
    $params['default_values'] = ['id_product' => (int) $params['id_product']];
    return $params;
  }

}