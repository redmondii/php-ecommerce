<?php

namespace ADIOS\Widgets\Products\Models;

class ProductFeature extends \ADIOS\Core\Model {
  var $sqlName = "products_features";
  var $urlBase = "Products/Features";
  var $tableTitle = "Product features";
  var $formTitleForInserting = "New product feature";
  var $formTitleForEditing = "Product feature";

  public function init() {

    // TODO: cisla zamenit za konstanty
    $this->enumValuesValueType = [
      1 => "Number",
      2 => "Text",
      3 => "Yes/No",
    ];

    // TODO: cisla zamenit za konstanty
    $this->enumValuesEntryMethod = [
      1 => "Slider",
      2 => "Select",
      3 => "Radio",
      4 => "Checkbox",
      5 => "Text",
    ];
  }

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
        "show_column" => ($languageIndex == 1),
        "is_searchable" => ($languageIndex == 1),
      ];
    }

    $columns = parent::columns(array_merge(
      $translatedColumns,
      [
        "icon" => [
          'type' => 'image',
          'title' => "Icon",
          'show_column' => TRUE,
          "subdir" => "feature_icons",
        ],

        "value_type" => [
          "type" => "int",
          "title" => "Type of value",
          "enum_values" => $this->enumValuesValueType,
          "show_column" => TRUE,
        ],

        "id_measurement_unit" => [
          "type" => "lookup",
          "title" => $this->translate("Measurement unit"),
          "model" => "Widgets/Settings/Models/Unit",
        ],

        "entry_method" => [
          "type" => "int",
          "title" => "Entry method",
          "enum_values" => $this->enumValuesEntryMethod,
          "show_column" => TRUE,
        ],

        "min" => [
          "type" => "int",
          "title" => "Minimum value",
          "show_column" => TRUE,
        ],

        "max" => [
          "type" => "int",
          "title" => "Maximum value",
          "show_column" => TRUE,
        ],

        "order_index" => [
          "type" => "int",
          "title" => "Order index",
          "show_column" => TRUE,
        ],

      ]
    ));

    return $columns;
  }

  public function lookupSqlValue($tableAlias = NULL) {
    $unitModel = $this->adios->getModel("Widgets/Settings/Models/Unit");

    $value = "
      concat(
        {%TABLE%}.name_lang_1,
        ' [',
        ifnull(
          (
            select
              unit
            from {$unitModel->table} u
            where u.id = {%TABLE%}.id_measurement_unit
          ),
          'N/A'
        ),
        ']'
      )
    ";

    return ($tableAlias !== NULL
      ? str_replace('{%TABLE%}', "`{$tableAlias}`", $value)
      : $value
    );
  }

  public function tableParams($params) {
    $params['show_search_button'] = FALSE;
    return $params;
  }

  public function formParams($data, $params) {
    $params['default_values'] = [
      'id_parent' => $params['id_parent']
    ];

    if ($data['id'] > 0) {
      $params['title'] = $data['name_lang_1'];
      $params['subtitle'] = "Product feature";
    }

    $params['columns']['id_parent']['readonly'] = $params['id_parent'] > 0;

    $tabTranslations = [];
    $domainLanguages = $this->adios->config['widgets']['Website']['domainLanguages'];

    $i = 1;
    foreach ($domainLanguages as $languageIndex => $languageName) {
      if ($i > 1) {
        $tabTranslations[] = ["html" => "<b>".hsc($languageName)."</b>"];
        $tabTranslations[] = "name_lang_{$languageIndex}";
        $tabTranslations[] = "description_lang_{$languageIndex}";
      }
      $i++;
    }

    if (count($tabTranslations) == 0) {
      $tabTranslations[] = ["html" => "No translations available."];
    }

    $params["template"] = [
      "columns" => [
        [
          "class" => "col-md-9 pl-0",
          "tabs" => [
            $this->translate("General") => [
              "name_lang_1",
              "description_lang_1",
              "icon",
              "value_type",
              "id_measurement_unit",
              "entry_method",
              "min",
              "max",
              "order_index",
            ],
            $this->translate("Translations") => $tabTranslations,
          ],
        ],
      ],
    ];

    return $params;
  }

}