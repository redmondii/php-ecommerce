<?php

namespace ADIOS\Widgets\Shipping\Models;

class PaymentService extends \ADIOS\Core\Model {
  var $sqlName = "shipping_payment_services";
  var $lookupSqlValue = "concat({%TABLE%}.name)";
  var $urlBase = "Shipping/PaymentServices";
  var $tableTitle = "Payment services";
  var $formTitleForInserting = "New payment service";
  var $formTitleForEditing = "Payment service";

  var $paymentPluginsEnumValues = [];

  public function init() {
    $this->paymentPluginsEnumValues = [
      "" => "-- Select a payment plugin --",
    ];

    foreach ($this->adios->websiteRenderer->getPaymentPlugins() as $paymentPlugin) {
      $this->paymentPluginsEnumValues[$paymentPlugin->name] = $paymentPlugin->name;
    }
  }

  public function columns(array $columns = []) {
    return parent::columns([
      "name" => [
        "type" => 'varchar',
        "title" => $this->translate("Name"),
        "description" => $this->translate("Name of the service as it will be displayed on the web."),
        "required" => TRUE,
        "show_column" => TRUE,
      ],

      "description" => [
        "type" => 'varchar',
        "title" => $this->translate("Description"),
        "description" => $this->translate("Optional. Some design themes may display this description on the web."),
        "show_column" => TRUE,
      ],

      "logo" => [
        "type" => 'image',
        "title" => $this->translate("Logo"),
        "description" => $this->translate("Optional. Some design themes may display the logo on the web."),
        "show_column" => TRUE,
      ],

      "is_enabled" => [
        "type" => 'boolean',
        "title" => $this->translate("Enabled"),
        "description" => "Only enabled delivery services will be available at the checkout.",
        "show_column" => TRUE,
      ],

      "connected_plugin" => [
        "type" => 'varchar',
        "title" => $this->translate("Connected plugin"),
        "description" => $this->translate("Select a plugin which will be used to process the payment."),
        "enum_values" => $this->deliveryPluginsEnumValues,
        "show_column" => TRUE,
      ],
    ]);
  }

  public function indexes($columns = []) {
    return parent::indexes([
      "connected_plugin" => [
        "type" => "index",
        "columns" => ["connected_plugin"],
      ]
    ]);
  }

  public function tableParams($params) {
    $params['header'] = "
      <p>".$this->translate("Manage payment services here. Insert only record for each contract with your payment service provider.")."</p>
    ";

    return $params;
  }

  public function formParams($data, $params) {
    $params["template"] = [
      "columns" => [
        [
          "tabs" => [
            "General" => [
              "name",
              "description",
              "logo",
            ],
            "Enable / Disable" => [
              "is_enabled",
            ],
            "Plugin" => [
              "connected_plugin",
            ],
          ]
        ],
      ],
    ];

    return $params;
  }
  
  public function getAll(string $keyBy = "id") {
    return self::get()->toArray();
  }

}