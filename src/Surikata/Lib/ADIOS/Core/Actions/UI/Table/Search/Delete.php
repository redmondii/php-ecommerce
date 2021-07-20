<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Table\Search;

class Delete extends \ADIOS\Core\Action {
  public function render() {
    $this->adios->deleteConfig(
      "UI/Table/savedSearches/{$this->params['searchGroup']}/{$this->params['searchName']}"
    );

    return TRUE;
  }
}
