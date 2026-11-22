<?php

namespace Addresses\Routes;

use SplitPHP\WebService;
use SplitPHP\Exceptions\Unauthorized;
use SplitPHP\Request;
use SplitPHP\ModsManager;

class Addresses extends WebService
{
  private const ADDRESS_PERMISSIONS = [
    'read'    => 'addresses.address.read',
    'manage'  => 'addresses.address.manage'
  ];

  public function init(): void
  {
    $this->addEndpoint('GET', '/v1/address/?key?', function (Request $request) {
      $this->auth(self::ADDRESS_PERMISSIONS);

      // Get the address:
      $params = [
        'ds_key' => $request->getRoute()->params['key'],
      ];

      $address = $this->getService('addresses/address')->get($params);

      if (!$address)
        return $this->response
          ->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($address);
    });

    $this->addEndpoint('GET', '/v1/address', function ($params) {
      $this->auth(self::ADDRESS_PERMISSIONS);

      // List the addresses:
      $addresses = $this->getService('addresses/address')
        ->list($params);

      return $this->response
        ->withStatus(200)
        ->withData($addresses);
    });

    $this->addEndpoint('POST', '/v1/address', function (Request $request) {
      $this->auth(self::ADDRESS_PERMISSIONS['manage']);

      // Create the address:
      $data = $request->getBody();
      $address = $this->getService('addresses/address')
        ->create($data);

      return $this->response
        ->withStatus(201)
        ->withData($address);
    });

    $this->addEndpoint('PUT', '/v1/address/?key?', function (Request $request) {
      $this->auth(self::ADDRESS_PERMISSIONS['manage']);

      $params = [
        'ds_key' => $request->getRoute()->params['key'],
      ];
      $data = $request->getBody();

      // Update the address:
      $rows = $this->getService('addresses/address')
        ->upd($params, $data);

      if (!$rows) return $this->response
        ->withStatus(404);

      return $this->response
        ->withStatus(204);
    });

    $this->addEndpoint('DELETE', '/v1/address/?key?', function (Request $request) {
      $this->auth(self::ADDRESS_PERMISSIONS['manage']);

      $params = [
        'ds_key' => $request->getRoute()->params['key'],
      ];

      // Remove the address:
      $rows = $this->getService('addresses/address')
        ->remove($params);

      if (!$rows) return $this->response
        ->withStatus(404);

      return $this->response
        ->withStatus(204);
    });
  }

  private function auth(array|string $permissions)
  {
    if (!ModsManager::modExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Unauthorized("Não autorizado.");

    // Validate user permissions:
    $this->getService('iam/permission')->requireAny($permissions);
  }
}
