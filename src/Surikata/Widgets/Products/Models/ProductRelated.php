<?php

namespace ADIOS\Widgets\Products\Models;

class ProductRelated extends \ADIOS\Core\Model {
  var $sqlName = "products_related";
  var $urlBase = "Products/{{ id_product }}/Related";
  var $tableTitle = "Related products";

  public function init() {
    $this->formTitleForInserting = $this->translate("New related product");
    $this->formTitleForEditing = $this->translate("Related product");
  }

  public function columns(array $columns = []) {
    return parent::columns([
      "id_product" => [
        "type" => "lookup",
        "model" => "Widgets/Products/Models/Product",
        "title" => $this->translate("Original product"),
        "readonly" => TRUE,
        "show_column" => FALSE,
      ],

      "id_related" => [
        "type" => "lookup",
        "model" => "Widgets/Products/Models/Product",
        "title" => $this->translate("Related product"),
        "show_column" => TRUE,
      ],
    ]);
  }

  public function tableParams($params) {
    $params["where"] = "{$this->table}.id_product = ".(int) $params['id_product'];
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