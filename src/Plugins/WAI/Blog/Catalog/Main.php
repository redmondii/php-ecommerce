<?php

namespace Surikata\Plugins\WAI\Blog {

  class Catalog extends \Surikata\Core\Web\Plugin {
    var $blogCatalogInfo = NULL;

    public function getBlogCatalogInfo($year = NULL, $month = NULL, $filter = NULL, $limit = NULL) {

      if (
        $this->blogCatalogInfo === NULL
        || $filter !== NULL
      ) {

        $blogDetailPlugin = new \Surikata\Plugins\WAI\Blog\Detail($this->websiteRenderer);

        $this->blogCatalogInfo = (new \ADIOS\Plugins\WAI\Blog\Catalog\Models\Blog($this->adminPanel))
          ->getByDate($year, $month, $filter["filteredTags"], $limit)
        ;

        foreach ($this->blogCatalogInfo as $key => $blog) {
          $this->blogCatalogInfo[$key]["url"] = $blogDetailPlugin->getWebPageUrl($blog);
        }
      }

      return $this->blogCatalogInfo;
    }

    public function getTwigParams($pluginSettings) {
      $twigParams = $pluginSettings;

      $twigParams['page'] = (int) ($this->websiteRenderer->urlVariables["page"] ?? 1);

      $filter = (new \Surikata\Plugins\WAI\Blog\Sidebar($this->websiteRenderer))
        ->getFilterInfo()
      ;

      $twigParams['blogs'] = $this->getBlogCatalogInfo(
        substr($this->websiteRenderer->urlVariables["year"], 1),
        substr($this->websiteRenderer->urlVariables["month"], 1),
        $filter
      );

      if ($twigParams["itemsPerPage"] == "") $twigParams["itemsPerPage"] = 6;

      $twigParams['pages'] = ceil(count($twigParams["blogs"]) / $twigParams['itemsPerPage']);
      $twigParams['fullDateUrl'] = $this->websiteRenderer->urlVariables["year"] . $this->websiteRenderer->urlVariables["month"];
      $twigParams['blogCatalogUrl'] = $this->getWebPageUrl();

      return $twigParams;
    }

  }

}

namespace ADIOS\Plugins\WAI\Blog {

  class Catalog extends \Surikata\Core\AdminPanel\Plugin {

    var $niceName = "Blogs";

    public function getSiteMap($pluginSettings = [], $webPageUrl = "") {

      return [
        $webPageUrl . '(/\d+)?(/\d+)?' => [
          1 => "year",
          2 => "month",
        ],
      ];
    }

    public function getSettingsForWebsite() {
      return [
        "itemsPerPage" => [
          "title"   => "Počet blogov na stranu",
          "type"    => "int",
        ],
        "showAuthor" => [
          "title"   => "Display author name",
          "type"    => "boolean",
        ],
        //"allowComments" => [
        //  "title" => "Povoliť komentovanie blogu",
        //  "type"  => "boolean"
        //]
      ];
    }

  }

}

