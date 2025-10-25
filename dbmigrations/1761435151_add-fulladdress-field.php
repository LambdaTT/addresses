<?php

namespace Addresses\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class AddFulladdressField extends Migration
{
  public function apply()
  {
    $this->Table('ADR_ADDRESS')
      ->text('tx_fulladdress')->nullable()->setDefaultValue(null);
  }
}
