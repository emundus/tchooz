<?php

namespace FedexRest\Entity;

class DangerousGoodsDetail
{

  public string $accessibility;

  public function setAccessibility(string $accessibility): DangerousGoodsDetail {
    $this->accessibility = $accessibility;
    return $this;
  }

  public function prepare(): array {
    $data = [];

    if (!empty($this->accessibility)) {
      $data['accessibility'] = $this->accessibility;
    }

    return $data;
  }

}
