<?php

namespace Addresses\Migrations;

use SplitPHP\DbMigrations\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableAddress extends Migration{
  public function apply(){
    $this->Table('ADR_ADDRESS', 'Address')
      ->id('id_adr_address')
      ->string('ds_key', 17)
      ->datetime('dt_created')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->datetime('dt_updated')->nullable()->setDefaultValue(null)
      ->int('id_iam_user_created')->nullable()->setDefaultValue(null)
      ->int('id_iam_user_updated')->nullable()->setDefaultValue(null)
      ->string('ds_label', 100)->nullable()->setDefaultValue(null)
      ->string('ds_zipcode', 10)
      ->string('ds_street', 100)
      ->string('ds_number', 10)
      ->string('ds_complement', 100)->nullable()->setDefaultValue(null)
      ->string('ds_neighborhood', 100)
      ->string('ds_city', 100)
      ->string('do_state', 2)
      ->string('ds_lat', 11)->nullable()->setDefaultValue(null)
      ->string('ds_lng', 12)->nullable()->setDefaultValue(null)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key');
  }
}