<?php

namespace Addresses\Routes;

use Exception;
use SplitPHP\WebService;

class Addresses extends WebService
{
  public function init()
  {
    $this->addEndpoint('GET', '/v1/address/?key?', function ($params) {
      $this->auth([
        'ADR_ADDRESS' => 'R'
      ]);

      // Get the address:
      $address = $this->getService('addresses/address')
        ->get(['ds_key' => $params['key']]);

      if (!$address)
        return $this->response
          ->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($address);
    });

    $this->addEndpoint('GET', '/v1/address/', function ($params) {
      $this->auth([
        'ADR_ADDRESS' => 'R'
      ]);

      // List the addresses:
      $addresses = $this->getService('addresses/address')
        ->list($params);

      return $this->response
        ->withStatus(200)
        ->withData($addresses);
    });

    $this->addEndpoint('POST', '/v1/address/', function ($data) {
      $this->auth([
        'ADR_ADDRESS' => 'C'
      ]);

      // Create the address:
      $address = $this->getService('addresses/address')
        ->create($data);

      return $this->response
        ->withStatus(201)
        ->withData($address);
    });

    $this->addEndpoint('PUT', '/v1/address/?key?', function ($input) {
      $this->auth([
        'ADR_ADDRESS' => 'U'
      ]);

      $params = [
        'ds_key' => $input['key'] ?? null,
      ];
      unset($input['key']);

      // Update the address:
      $rows = $this->getService('addresses/address')
        ->update($params, $input);

      if (!$rows) return $this->response
        ->withStatus(404);

      return $this->response
        ->withStatus(204);
    });

    $this->addEndpoint('DELETE', '/v1/address/?key?', function ($params) {
      $this->auth([
        'ADR_ADDRESS' => 'D'
      ]);

      // Remove the address:
      $rows = $this->getService('addresses/address')
        ->remove(['ds_key' => $params['key']]);

      if (!$rows) return $this->response
        ->withStatus(404);

      return $this->response
        ->withStatus(204);
    });
  }

  private function auth(array $permissions)
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Exception("Não autorizado.", NOT_AUTHORIZED);

    // Validate user permissions:
    $this->getService('iam/permission')
      ->validatePermissions($permissions);
  }
}
