<?php

namespace Addresses\Commands;

use SplitPHP\Cli;
use SplitPHP\Utils;

class Commands extends Cli
{
  public function init()
  {
    $this->addCommand('list', function ($args) {
      $getRows = function ($params) {
        return $this->getService('addresses/address')->list($params);
      };

      $columns = [
        'id_adr_address'           => 'ID',
        'dt_created'              => 'Created At',
        'ds_label'                => 'Label',
        'ds_zipcode'              => 'Zip Code',
        'ds_city'                => 'City',
        'do_state'                => 'State',
        'ds_lat'                => 'Latitude',
        'ds_lng'                => 'Longitude',
      ];

      $this->getService('utils/misc')->printDataTable("Addresses List", $getRows, $columns, $args);
    });

    $this->addCommand('create', function () {
      Utils::printLn("Welcome to the Address Create Command!");
      Utils::printLn("This command will help you add a new address.");
      Utils::printLn();
      Utils::printLn(" >> Please follow the prompts to define your address informations.");
      Utils::printLn();
      Utils::printLn("  >> New Address:");
      Utils::printLn("------------------------------------------------------");

      $address = $this->getService('utils/clihelper')->inputForm([
        'ds_label' => [
          'label' => 'Label',
          'required' => true,
          'length' => 100,
        ],
        'ds_zipcode' => [
          'label' => 'Zip Code',
          'required' => true,
          'length' => 10,
        ],
        'ds_street' => [
          'label' => 'Street',
          'required' => true,
          'length' => 100,
        ],
        'ds_number' => [
          'label' => 'Number',
          'required' => true,
          'length' => 10,
        ],
        'ds_complement' => [
          'label' => 'Complement',
          'required' => false,
          'length' => 100,
        ],
        'ds_neighborhood' => [
          'label' => 'Neighborhood',
          'required' => true,
          'length' => 100,
        ],
        'ds_city' => [
          'label' => 'City',
          'required' => true,
          'length' => 100,
        ],
        'do_state' => [
          'label' => 'State',
          'required' => true,
          'length' => 2,
        ],
      ]);

      $record = $this->getService('addresses/address')
        ->create($address);

      Utils::printLn("  >> Address added successfully!");
      foreach ($record as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }
    });

    $this->addCommand('remove', function () {
      Utils::printLn("Welcome to the Address Removal Command!");
      Utils::printLn();
      Utils::printLn('Enter the Address ID you wish to remove:');
      $addressId = $this->getService('utils/misc')->persistentCliInput(
        function ($v) {
          return !empty($v) && is_numeric($v);
        },
        "Address ID must be an integer and cannot be empty or zero. Please try again:"
      );

      $this->getDao('ADR_ADDRESS')
        ->filter('id_adr_address')->equalsTo($addressId)
        ->delete();
      Utils::printLn("  >> Address '{$addressId}' removed successfully!");
    });

    // Help command
    $this->addCommand('help', function () {
      /** @var \Utils\Services\CliHelper $helper */
      $helper = $this->getService('utils/clihelper');
      Utils::printLn($helper->ansi(strtoupper("Welcome to the Addresses Help Center!"), 'color: magenta; font-weight: bold'));

      // 1) Define metadata for each command
      $commands = [
        ':list'   => [
          'usage' => 'addresses:list [--limit=<n>] [--sort-by=<field>] [--sort-direction=<dir>] [--page=<n>]',
          'desc'  => 'Page through existing addresses.',
          'flags' => [
            '--limit=<n>'          => 'Items per page (default 10)',
            '--sort-by=<field>'    => 'Field to sort by',
            '--sort-direction=<d>' => 'ASC or DESC (default ASC)',
            '--page=<n>'           => 'Page number (default 1)',
          ],
        ],
        ':create' => [
          'usage' => 'addresses:create',
          'desc'  => 'Interactively create a new address.',
        ],
        ':remove' => [
          'usage' => 'addresses:remove',
          'desc'  => 'Delete an address by its ID.',
        ],
        ':help'             => [
          'usage' => 'addresses:help',
          'desc'  => 'Show this help screen.',
        ],
      ];

      // 2) Summary table
      Utils::printLn($helper->ansi("\nAvailable commands:\n", 'color: cyan; text-decoration: underline'));

      $rows = [
        [
          'cmd'  => 'addresses:list',
          'desc' => 'Page through existing addresses',
          'opts' => '--limit, --sort-by, --sort-direction, --page',
        ],
        [
          'cmd'  => 'addresses:create',
          'desc' => 'Interactively create a new address',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'addresses:remove',
          'desc' => 'Delete an address by ID',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'addresses:help',
          'desc' => 'Show this help screen',
          'opts' => '(no flags)',
        ],
      ];

      $helper->table($rows, [
        'cmd'  => 'Command',
        'desc' => 'Description',
        'opts' => 'Options',
      ]);

      // 3) Detailed usage lists
      foreach ($commands as $cmd => $meta) {
        Utils::printLn($helper->ansi("\n{$cmd}", 'color: yellow; font-weight: bold'));
        Utils::printLn("  Usage:   {$meta['usage']}");
        Utils::printLn("  Purpose: {$meta['desc']}");

        if (!empty($meta['flags'])) {
          Utils::printLn("  Options:");
          $flagLines = [];
          foreach ($meta['flags'] as $flag => $explain) {
            $flagLines[] = "{$flag}  — {$explain}";
          }
          $helper->listItems($flagLines, false, '    •');
        }
      }

      Utils::printLn(''); // trailing newline
    });
  }
}
