<?php

namespace Addresses\Services;

use SplitPHP\Service;
use Exception;
use SplitPHP\Helpers;

class Address extends Service
{
  public function list($params = [])
  {
    return $this->getDao('ADR_ADDRESS')
      ->bindParams($params)
      ->find();
  }

  public function get($params = [])
  {
    return $this->getDao('ADR_ADDRESS')
      ->bindParams($params)
      ->first();
  }

  public function create($data)
  {
    // Data Blacklisting:
    $data = $this->getService('utils/misc')
      ->dataBlacklist($data, [
        'id_adr_address',
        'ds_key',
        'dt_created',
        'dt_updated',
        'id_iam_user_created',
        'id_iam_user_updated',
        'ds_lat',
        'ds_lng',
      ]);

    // Data Validation:
    if (!$this->areAllFieldsPresent($data)) {
      throw new Exception('Os dados do endereço estão incompletos.', BAD_REQUEST);
    }

    // Gather additional data:
    $r = Helpers::cURL()
      ->setDataAsJson([
        'format' => 'json',
        'q' => $this->buildFullAddress($data),
      ])
      ->get('https://nominatim.openstreetmap.org/search');

    if ($r->status != 200) {
      throw new Exception('There was an error on the attempt to retrieve geolocation data.');
    }

    $geo = $r->data[0];

    // Automatically data filling:
    $data['ds_key'] = 'adr-' . uniqid();
    $data['id_iam_user_created'] = $this->getLoggedUserId();
    $data['ds_lat'] = $geo->lat ?? null;
    $data['ds_lng'] = $geo->lon ?? null;

    return $this->getDao('ADR_ADDRESS')
      ->insert($data);
  }

  public function upd($params, $data)
  {
    // Data Blacklisting:
    $data = $this->getService('utils/misc')
      ->dataBlacklist($data, [
        'id_adr_address',
        'ds_key',
        'dt_created',
        'dt_updated',
        'id_iam_user_created',
        'id_iam_user_updated',
        'ds_lat',
        'ds_lng',
      ]);

    $rows = 0;

    foreach ($this->list($params) as $address) {
      foreach ($data as $key => $value)
        $address->$key = $value;

      // Gather additional data:
      $r = Helpers::cURL()
        ->setDataAsJson([
          'format' => 'json',
          'q' => $this->buildFullAddress($address),
        ])
        ->get('https://nominatim.openstreetmap.org/search');

      if ($r->status != 200) {
        throw new Exception('There was an error on the attempt to retrieve geolocation data.');
      }

      $geo = $r->data[0];

      // Automatically data filling:
      $data['id_iam_user_updated'] = $this->getLoggedUserId();
      $data['dt_updated'] = date('Y-m-d H:i:s');
      $data['ds_lat'] = $geo->lat ?? null;
      $data['ds_lng'] = $geo->lon ?? null;

      $rows += $this->getDao('ADR_ADDRESS')
        ->filter('id_adr_address')->equalsTo($address['id_adr_address'])
        ->update($data);
    }

    return $rows;
  }

  public function remove($params)
  {
    return $this->getDao('ADR_ADDRESS')
      ->bindParams($params)
      ->delete();
  }

  public function buildFullAddress($data)
  {
    $data = (array) $data;
    if (!$this->areAllFieldsPresent($data)) {
      throw new Exception('Os dados do endereço estão incompletos.', BAD_REQUEST);
    }
    // Build the full address string
    return implode(', ', array_filter([
      $data['ds_zipcode'],
      $data['ds_street'],
      $data['ds_number'],
      $data['ds_complement'] ?? null,
      $data['ds_neighborhood'],
      $data['do_state'],
      $data['ds_city'],
    ]));
  }

  private function areAllFieldsPresent($data)
  {
    $required = [
      'ds_zipcode',
      'ds_street',
      'ds_number',
      'ds_neighborhood',
      'ds_city',
      'do_state',
    ];

    return empty(array_diff($required, array_keys($data)));
  }

  private function getLoggedUserId()
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return null;

    $user = $this->getService('iam/session')
      ->getLoggedUser();

    if (empty($user)) return null;

    return $user->id_iam_user;
  }
}
