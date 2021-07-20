<?php

namespace ADIOS\Widgets;

class Products extends \ADIOS\Core\Widget {
  public function init() {
    $this->languageDictionary["en"] = [
    ];

    if ($this->adios->hasUserRole(\Surikata\Core\AdminPanel\Loader::USER_ROLE_PRODUCT_MANAGER)) {
      $this->adios->config['desktop']['sidebarItems']['Products'] = [
        "fa_icon" => "fas fa-pizza-slice",
        "title" => "Products",
        "onclick" => "desktop_update('Products');",
        "sub" => [
          [
            "title" => $this->translate("Categories"),
            "sub" => [
              [
                "title" => $this->translate("View as table"),
                "onclick" => "desktop_update('Products/Categories');",
              ],
              [
                "title" => $this->translate("View as tree"),
                "onclick" => "window_render('Products/Categories/Tree');",
              ],
            ]
          ],
          // [
          //   "title" => $this->translate("Sets"),
          //   "onclick" => "desktop_update('Products/Sets');",
          // ],
          [
            "title" => $this->translate("Settings"),
            "sub" => [
              [
                "title" => $this->translate("Features"),
                "onclick" => "desktop_update('Products/Features');",
              ],
              [
                "title" => $this->translate("Brands"),
                "onclick" => "desktop_update('Brands');",
              ],
              [
                "title" => $this->translate("Services"),
                "onclick" => "desktop_update('Services');",
              ],
            ],
          ],
        ],
      ];
    }
  }
}