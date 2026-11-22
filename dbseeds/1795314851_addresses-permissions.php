<?php

namespace Addresses\Seeds;

use SplitPHP\DbManager\Seed;
use SplitPHP\ModsManager;

class AddressesPermissions extends Seed
{
  public function apply()
  {
    if (!ModsManager::modExists('iam')) return;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Informações de Endereços')
      ->onField('ds_tag')->setFixedValue('addresses.address.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Informações de Endereços')
      ->onField('ds_tag')->setFixedValue('addresses.address.manage')
    ;
  }
}
