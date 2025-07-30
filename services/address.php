<?php

namespace Addresses\Services;

use SplitPHP\Service;
use Exception;
use SplitPHP\Exceptions\BadRequest;
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
      throw new BadRequest('Os dados do endereço estão incompletos.');
    }

    // Gather additional data:
    $appName = APPLICATION_NAME;
    $urlApp = URL_APPLICATION;
    $r = Helpers::cURL()
      ->setHeader("User-Agent: {$appName}/1.0 ({$urlApp})")
      ->setData([
        'format' => 'json',
        'q' => $this->buildFullAddress($data, ['ds_zipcode']),
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
      $address = (array) $address;
      foreach ($data as $key => $value)
        $address[$key] = $value;

      // Gather additional data:
      $appName = APPLICATION_NAME;
      $urlApp = URL_APPLICATION;
      $r = Helpers::cURL()
        ->setHeader("User-Agent: {$appName}/1.0 ({$urlApp})")
        ->setData([
          'format' => 'json',
          'q' => $this->buildFullAddress($address, ['ds_zipcode']),
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

  public function buildFullAddress($data, $ignore = [])
  {
    $data = (array) $data;
    if (!$this->areAllFieldsPresent($data, $ignore)) {
      throw new BadRequest('Os dados do endereço estão incompletos.');
    }
    
    // Build the full address string
    return implode(', ', array_filter([
      !in_array('ds_zipcode', $ignore) ? $data['ds_zipcode'] : null,
      !in_array('ds_street', $ignore) ? $data['ds_street'] : null,
      !in_array('ds_number', $ignore) ? $data['ds_number'] : null,
      !in_array('ds_complement', $ignore) ? $data['ds_complement'] : null,
      !in_array('ds_neighborhood', $ignore) ? $data['ds_neighborhood'] : null,
      !in_array('do_state', $ignore) ? $data['do_state'] : null,
      !in_array('ds_city', $ignore) ? $data['ds_city'] : null,
    ]));
  }

  private function areAllFieldsPresent($data, $ignore = [])
  {
    $required = [
      'ds_zipcode',
      'ds_street',
      'ds_number',
      'ds_neighborhood',
      'ds_city',
      'do_state',
    ];

    foreach ($ignore as $field) {
      if (isset($required[$field])) {
        unset($required[$field]);
      }
    }

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
