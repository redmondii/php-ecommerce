<?php

namespace ADIOS\Actions\Orders;

use ADIOS\Widgets\Orders\Models\Order;

class PlaceOrder extends \ADIOS\Core\Action {
  public function render() {

    $orderData = [];

    $values = json_decode($this->params["values"] ?? "", true);

    if (!is_array($values)) {
      throw new \ADIOS\Widgets\Orders\Exceptions\InvalidOrderDataFormat();
    }

    foreach ($values as $key => $value) {
      $orderData[$key] = $value;
    }

    $orderModel = new Order($this->adios);
    $orderData = $orderModel->addCustomerInfoToOrderData($orderData);

    return $orderModel
      ->placeOrder($orderData, NULL, NULL, FALSE)
    ;

  }
}