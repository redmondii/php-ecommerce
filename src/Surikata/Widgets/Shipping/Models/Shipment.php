<?php

namespace ADIOS\Widgets\Shipping\Models;

class Shipment extends \ADIOS\Core\Model {
  var $sqlName = "shipping_shipments";
  var $lookupSqlValue = "concat({%TABLE%}.name)";
  var $urlBase = "DeliveryAndPayment/Prices";

  public function init() {
    $this->tableTitle = $this->translate("Shipment prices");
    $this->formTitleForInserting = $this->translate("New shipment");
    $this->formTitleForEditing = $this->translate("Shipment");
  }

  public function columns(array $columns = []) {
    return parent::columns([
      "id_delivery_service" => [
        "type" => "lookup",
        "title" => $this->translate("Delivery service"),
        "model" => "Widgets/Shipping/Models/DeliveryService",
        "show_column" => TRUE,
        'required' => TRUE
      ],

      "id_payment_service" => [
        "type" => "lookup",
        "title" => $this->translate("Payment service"),
        "model" => "Widgets/Shipping/Models/PaymentService",
        "show_column" => TRUE,
        'required' => TRUE
      ],

      "id_destination_country" => [
        "type" => "lookup",
        "title" => $this->translate("Country of destination"),
        "model" => "Widgets/Shipping/Models/DestinationCountry",
        "show_column" => TRUE,
        'required' => TRUE
      ],

      "name" => [
        'type' => 'varchar',
        'title' => $this->translate("Shipment name"),
        'show_column' => TRUE,
        'required' => TRUE
      ],

      "description" => [
        'type' => 'text',
        'title' => $this->translate("Shipment description"),
      ],

      "logo" => [
        'type' => 'image',
        'title' => $this->translate("Shipment logo"),
        "description" => $this->translate("Supported image extensions: jpg, gif, png, jpeg"),
        'show_column' => TRUE,
      ],

      "is_enabled" => [
        "type" => "boolean",
        "title" => $this->translate("Enable"),
        "show_column" => TRUE
      ],

      "order_index" => [
        "type" => "int",
        "title" => $this->translate("Order index"),
      ]

    ]);
  }

  public function indexes($columns = []) {
    return parent::indexes([
      [
        "type" => "index",
        "columns" => ["id_delivery_service", "id_payment_service", "id_destination_country"],
      ]
    ]);
  }

  public function tableParams($params) {
    $params['header'] = "
      <p>".$this->translate("Here you connect your contracted delivery services, contracted payment services and countries of destination to a unique shipment.")."</p>
      <p>".$this->translate("Prices based on order's weight or value are defined for each shipment separately.")."</p>
    ";

    return $params;
  }

  public function formParams($data, $params) {
    $params["template"] = [
      "columns" => [
        [
          "tabs" => [
            $this->translate("Delivery / Payment / Destination Country") => [
              "id_delivery_service",
              "id_payment_service",
              "id_destination_country",
            ],
            $this->translate("Shipment") => [
              "name",
              "description",
              "logo",
            ],
            $this->translate("Enable / Disable") => [
              "is_enabled",
            ],
            $this->translate("Prices") => [
              "action" => "UI/Table",
              "params" => [
                "model"    => "Widgets/Shipping/Models/ShipmentPrice",
                "id_shipment" => (int) $data['id'],
              ]
            ],
            $this->translate("Miscelaneous") => [
              "order_index",
            ],
          ],
        ],
      ],
    ];

    return $params;
  }

  public function price() {
    return $this->hasMany(\ADIOS\Widgets\Shipping\Models\ShipmentPrice::class, 'id_shipment', 'id');
  }

  public function payment() {
    return $this->hasOne(\ADIOS\Widgets\Shipping\Models\PaymentService::class, "id", "id_payment_service");
  }

  public function delivery() {
    return $this->hasOne(\ADIOS\Widgets\Shipping\Models\DeliveryService::class, "id", "id_delivery_service");
  }

  public function country() {
    return $this->hasOne(\ADIOS\Widgets\Shipping\Models\DestinationCountry::class, "id", "id_destination_country");
  }

  public function getByIdDeliveryService($idDelivery) {
    return $this
      ->with('payment')
      ->where([
        ['id_delivery_service', $idDelivery],
        ['is_enabled', 1]
      ])
      ->get()
      ->toArray()
    ;
  }

  public function getByCartSummary(array $summary) {
    return 
      $this
      ->with([
        'delivery',
        'payment',
        'country',
        'price' => function($q) use ($summary) {
          $q->where([
            ['delivery_fee_calculation_method', '=', 1],	
            ['price_from', '<=', $summary['priceInclVAT']],
            ['price_to', '>=', $summary['priceInclVAT']]
          ]);
          $q->orWhere([
            ['delivery_fee_calculation_method', '=', 2],	
            ['weight_from', '<=', $summary['weight']],
            ['weight_to', '>=', $summary['weight']]
          ]);
        }
      ])
      ->whereHas('price', function ($q) use ($summary){
        $q->where([
          ['delivery_fee_calculation_method', '=', 1],	
          ['price_from', '<=', $summary['priceInclVAT']],
          ['price_to', '>=', $summary['priceInclVAT']]
        ]);
        $q->orWhere([
          ['delivery_fee_calculation_method', '=', 2],	
          ['weight_from', '<=', $summary['weight']],
          ['weight_to', '>=', $summary['weight']]
        ]);
      })
      ->where('is_enabled', 1)
      ->get()
      ->toArray()
    ;
  }

  public function getShipment($idDelivery, $idPayment) {
    $query =
      $this
      ->where([
        ['id_delivery_service', '=', $idDelivery],
        ['id_payment_service', '=', $idPayment]
      ])
      ->get()
      ->toArray()
    ;

    return $query ? reset($query) : NULL;
  }

}