<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use CommonDBTM;
use DBConnection;
use DbTestCase;

/* Test for inc/search.class.php */

class Search extends DbTestCase {

   private function doSearch($itemtype, $params, array $forcedisplay = []) {
      global $DEBUG_SQL;

      // check param itemtype exists (to avoid search errors)
      if ($itemtype !== 'AllAssets') {
         $this->class($itemtype)->isSubClassof('CommonDBTM');
      }

      // login to glpi if needed
      if (!isset($_SESSION['glpiname'])) {
         $this->login();
      }

      // force session in debug mode (to store & retrieve sql errors)
      $glpi_use_mode             = $_SESSION['glpi_use_mode'];
      $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;

      // don't compute last request from session
      $params['reset'] = 'reset';

      // do search
      $params = \Search::manageParams($itemtype, $params);
      $data   = \Search::getDatas($itemtype, $params, $forcedisplay);

      // append existing errors to returned data
      $data['last_errors'] = [];
      if (isset($DEBUG_SQL['errors'])) {
         $data['last_errors'] = implode(', ', $DEBUG_SQL['errors']);
         unset($DEBUG_SQL['errors']);
      }

      // restore glpi mode to previous
      $_SESSION['glpi_use_mode'] = $glpi_use_mode;

      // do not store this search from session
      \Search::resetSaveSearch();

      $this->checkSearchResult($data);

      return $data;
   }

   public function testMetaComputerSoftwareLicense() {
      $search_params = ['is_deleted'   => 0,
                        'start'        => 0,
                        'criteria'     => [0 => ['field'      => 'view',
                                                 'searchtype' => 'contains',
                                                 'value'      => '']],
                        'metacriteria' => [0 => ['link'       => 'AND',
                                                 'itemtype'   => 'Software',
                                                 'field'      => 163,
                                                 'searchtype' => 'contains',
                                                 'value'      => '>0'],
                                           1 => ['link'       => 'AND',
                                                 'itemtype'   => 'Software',
                                                 'field'      => 160,
                                                 'searchtype' => 'contains',
                                                 'value'      => 'firefox']]];

      $data = $this->doSearch('Computer', $search_params);

      $this->string($data['sql']['search'])
         ->matches('/'
            . 'LEFT JOIN\s*`glpi_items_softwareversions`\s*AS\s*`glpi_items_softwareversions_[^`]+_Software`\s*ON\s*\('
            . '`glpi_items_softwareversions_[^`]+_Software`\.`items_id`\s*=\s*`glpi_computers`.`id`'
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`itemtype`\s*=\s*\'Computer\''
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`is_deleted`\s*=\s*0'
            . '\)/im');
   }

   public function testSoftwareLinkedToAnyComputer() {
      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => '',
            ],
         ],
         'metacriteria' => [
            [
               'link'       => 'AND NOT',
               'itemtype'   => 'Computer',
               'field'      => 2,
               'searchtype' => 'contains',
               'value'      => '^$', // search for "null" id
            ],
         ],
      ];

      $data = $this->doSearch('Software', $search_params);

      $this->string($data['sql']['search'])
         ->matches("/HAVING\s*\(`ITEM_Computer_2`\s+IS\s+NOT\s+NULL\s*\)/");
   }

   public function testMetaComputerUser() {
      $search_params = ['is_deleted'   => 0,
                        'start'        => 0,
                        'search'       => 'Search',
                        'criteria'     => [0 => ['field'      => 'view',
                                                 'searchtype' => 'contains',
                                                 'value'      => '']],
                                           // user login
                        'metacriteria' => [0 => ['link'       => 'AND',
                                                 'itemtype'   => 'User',
                                                 'field'      => 1,
                                                 'searchtype' => 'equals',
                                                 'value'      => 2],
                                           // user profile
                                           1 => ['link'       => 'AND',
                                                 'itemtype'   => 'User',
                                                 'field'      => 20,
                                                 'searchtype' => 'equals',
                                                 'value'      => 4],
                                           // user entity
                                           2 => ['link'       => 'AND',
                                                 'itemtype'   => 'User',
                                                 'field'      => 80,
                                                 'searchtype' => 'equals',
                                                 'value'      => 0],
                                           // user profile
                                           3 => ['link'       => 'AND',
                                                 'itemtype'   => 'User',
                                                 'field'      => 13,
                                                 'searchtype' => 'equals',
                                                 'value'      => 1]]];

      $this->doSearch('Computer', $search_params);
   }

   public function testSubMetaTicketComputer() {
      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'search'       => 'Search',
         'criteria'     => [
            0 => [
               'field'      => 12,
               'searchtype' => 'equals',
               'value'      => 'notold'
            ],
            1 => [
               'link'       => 'AND',
               'criteria'   => [
                  0 => [
                     'field'      => 'view',
                     'searchtype' => 'contains',
                     'value'      => 'test1'
                  ],
                  1 => [
                     'link'       => 'OR',
                     'field'      => 'view',
                     'searchtype' => 'contains',
                     'value'      => 'test2'
                  ],
                  2 => [
                     'link'       => 'OR',
                     'meta'       => true,
                     'itemtype'   => 'Computer',
                     'field'      => 1,
                     'searchtype' => 'contains',
                     'value'      => 'test3'
                  ],
               ]
            ],
         ],
      ];

      $this->doSearch('Ticket', $search_params);
   }

   public function testFlagMetaComputerUser() {
      $search_params = [
         'reset'        => 'reset',
         'is_deleted'   => 0,
         'start'        => 0,
         'search'       => 'Search',
         'criteria'     => [
            0 => [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => ''
            ],
            // user login
            1 => [
               'link'       => 'AND',
               'itemtype'   => 'User',
               'field'      => 1,
               'meta'       => 1,
               'searchtype' => 'equals',
               'value'      => 2
            ],
            // user profile
            2 => [
               'link'       => 'AND',
               'itemtype'   => 'User',
               'field'      => 20,
               'meta'       => 1,
               'searchtype' => 'equals',
               'value'      => 4
             ],
            // user entity
            3 => [
               'link'       => 'AND',
               'itemtype'   => 'User',
               'field'      => 80,
               'meta'       => 1,
               'searchtype' => 'equals',
               'value'      => 0
            ],
            // user profile
            4 => [
               'link'       => 'AND',
               'itemtype'   => 'User',
               'field'      => 13,
               'meta'       => 1,
               'searchtype' => 'equals',
               'value'      => 1
            ]
         ]
      ];

      $data = $this->doSearch('Computer', $search_params);

      $this->string($data['sql']['search'])
         ->contains("LEFT JOIN  `glpi_users`")
         ->contains("LEFT JOIN `glpi_profiles`  AS `glpi_profiles_")
         ->contains("LEFT JOIN `glpi_entities`  AS `glpi_entities_");
   }

   public function testNestedAndMetaComputer() {
      $search_params = [
         'reset'      => 'reset',
         'is_deleted' => 0,
         'start'      => 0,
         'search'     => 'Search',
         'criteria'   => [
            [
               'link'       => 'AND',
               'field'      => 1,
               'searchtype' => 'contains',
               'value'      => 'test',
            ], [
               'link'       => 'AND',
               'itemtype'   => 'Software',
               'meta'       => 1,
               'field'      => 1,
               'searchtype' => 'equals',
               'value'      => 10784,
            ], [
               'link'       => 'OR',
               'criteria'   => [
                  [
                     'link'       => 'AND',
                     'field'      => 2,
                     'searchtype' => 'contains',
                     'value'      => 'test',
                  ], [
                     'link'       => 'OR',
                     'field'      => 2,
                     'searchtype' => 'contains',
                     'value'      => 'test2',
                  ], [
                     'link'       => 'AND',
                     'field'      => 3,
                     'searchtype' => 'equals',
                     'value'      => 11,
                  ], [
                     'link'       => 'AND',
                     'criteria'   => [
                        [
                           'field'      => 70,
                           'searchtype' => 'equals',
                           'value'      => 2,
                        ], [
                           'link'       => 'OR',
                           'field'      => 70,
                           'searchtype' => 'equals',
                           'value'      => 3,
                        ]
                     ]
                  ]
               ]
            ], [
               'link'       => 'AND NOT',
               'itemtype'   => 'Budget',
               'meta'       => 1,
               'field'      => 2,
               'searchtype' => 'contains',
               'value'      => 5,
            ], [
               'link'       => 'AND NOT',
               'itemtype'   => 'Printer',
               'meta'       => 1,
               'field'      => 1,
               'searchtype' => 'contains',
               'value'      => 'HP',
            ]
         ]
      ];

      $data = $this->doSearch('Computer', $search_params);

      $this->string($data['sql']['search'])
         // join parts
         ->matches('/LEFT JOIN\s*`glpi_items_softwareversions`\s*AS `glpi_items_softwareversions_Software`/im')
         ->matches('/LEFT JOIN\s*`glpi_softwareversions`\s*AS `glpi_softwareversions_Software`/im')
         ->matches('/LEFT JOIN\s*`glpi_softwares`\s*ON\s*\(`glpi_softwareversions_Software`\.`softwares_id`\s*=\s*`glpi_softwares`\.`id`\)/im')
         ->matches('/LEFT JOIN\s*`glpi_infocoms`\s*AS\s*`glpi_infocoms_Budget`\s*ON\s*\(`glpi_computers`\.`id`\s*=\s*`glpi_infocoms_Budget`\.`items_id`\s*AND\s*`glpi_infocoms_Budget`.`itemtype`\s*=\s*\'Computer\'\)/im')
         ->matches('/LEFT JOIN\s*`glpi_budgets`\s*ON\s*\(`glpi_infocoms_Budget`\.`budgets_id`\s*=\s*`glpi_budgets`\.`id`/im')
         ->matches('/LEFT JOIN\s*`glpi_computers_items`\s*AS `glpi_computers_items_Printer`\s*ON\s*\(`glpi_computers_items_Printer`\.`computers_id`\s*=\s*`glpi_computers`\.`id`\s*AND\s*`glpi_computers_items_Printer`.`itemtype`\s*=\s*\'Printer\'\s*AND\s*`glpi_computers_items_Printer`.`is_deleted`\s*=\s*0\)/im')
         ->matches('/LEFT JOIN\s*`glpi_printers`\s*ON\s*\(`glpi_computers_items_Printer`\.`items_id`\s*=\s*`glpi_printers`\.`id`/im')
         // match where parts
         ->contains("`glpi_computers`.`is_deleted` = 0")
         ->contains("AND `glpi_computers`.`is_template` = 0")
         ->contains("`glpi_computers`.`entities_id` IN ('1', '2', '3')")
         ->contains("OR (`glpi_computers`.`is_recursive`='1'".
                    " AND `glpi_computers`.`entities_id` IN (0))")
         ->contains("`glpi_computers`.`name`  LIKE '%test%'")
         ->contains("AND (`glpi_softwares`.`id` = '10784')")
         ->contains("OR (`glpi_computers`.`id`  LIKE '%test2%'")
         ->contains("AND (`glpi_locations`.`id` = '11')")
         ->contains("(`glpi_users`.`id` = '2')")
         ->contains("OR (`glpi_users`.`id` = '3')")
         // match having
         ->matches("/HAVING\s*\(`ITEM_Budget_2`\s+<>\s+5\)\s+AND\s+\(\(`ITEM_Printer_1`\s+NOT LIKE\s+'%HP%'\s+OR\s+`ITEM_Printer_1`\s+IS NULL\)\s*\)/");
   }

   function testViewCriterion() {
      $data = $this->doSearch('Computer', [
         'reset'      => 'reset',
         'is_deleted' => 0,
         'start'      => 0,
         'search'     => 'Search',
         'criteria'   => [
            [
               'link'       => 'AND',
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => 'test',
            ],
         ]
      ]);

      $default_charset = DBConnection::getDefaultCharset();

      $this->string($data['sql']['search'])
         ->contains("`glpi_computers`.`is_deleted` = 0")
         ->contains("AND `glpi_computers`.`is_template` = 0")
         ->contains("`glpi_computers`.`entities_id` IN ('1', '2', '3')")
         ->contains("OR (`glpi_computers`.`is_recursive`='1'".
                    " AND `glpi_computers`.`entities_id` IN (0))")
         ->matches("/`glpi_computers`\.`name`  LIKE '%test%'/")
         ->matches("/OR\s*\(`glpi_entities`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_states`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_manufacturers`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computers`\.`serial`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computertypes`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computermodels`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_locations`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(CONVERT\(`glpi_computers`\.`date_mod` USING {$default_charset}\)\s*LIKE '%test%'\s*\)\)/");
   }

   public function testSearchOnRelationTable() {
      $data = $this->doSearch(\Change_Ticket::class, [
         'reset'      => 'reset',
         'is_deleted' => 0,
         'start'      => 0,
         'search'     => 'Search',
         'criteria'   => [
            [
               'link'       => 'AND',
               'field'      => '3',
               'searchtype' => 'equals',
               'value'      => '1',
            ],
         ]
      ]);

      $this->string($data['sql']['search'])
         ->contains("`glpi_changes`.`id` AS `ITEM_Change_Ticket_3`")
         ->contains("`glpi_changes_tickets`.`changes_id` = `glpi_changes`.`id`")
         ->contains("`glpi_changes`.`id` = '1'");
   }

   public function testUser() {
      $search_params = ['is_deleted'   => 0,
                        'start'        => 0,
                        'search'       => 'Search',
                                                     // profile
                        'criteria'     => [0 => ['field'      => '20',
                                                 'searchtype' => 'contains',
                                                 'value'      => 'super-admin'],
                                           // login
                                           1 => ['link'       => 'AND',
                                                 'field'      => '1',
                                                 'searchtype' => 'contains',
                                                 'value'      => 'glpi'],
                                           // entity
                                           2 => ['link'       => 'AND',
                                                 'field'      => '80',
                                                 'searchtype' => 'equals',
                                                 'value'      => 0],
                                           // is not not active
                                           3 => ['link'       => 'AND',
                                                 'field'      => '8',
                                                 'searchtype' => 'notequals',
                                                 'value'      => 0]]];
      $data = $this->doSearch('User', $search_params);

      //expecting one result
      $this->integer($data['data']['totalcount'])->isIdenticalTo(1);
   }

   /**
    * This test will add all searchoptions in each itemtype and check if the
    * search give a SQL error
    *
    * @return void
    */
   public function testSearchOptions() {
      $classes = $this->getSearchableClasses();
      foreach ($classes as $class) {
         $item = new $class();

         //load all options; so rawSearchOptionsToAdd to be tested
         $options = \Search::getCleanedOptions($item->getType());

         $multi_criteria = [];
         foreach ($options as $key => $data) {
            if (!is_int($key) || ($criterion_params = $this->getCriterionParams($item, $key, $data)) === null) {
               continue;
            }

            // do a search query based on current search option
            $this->doSearch(
               $class,
               [
                  'is_deleted'   => 0,
                  'start'        => 0,
                  'criteria'     => [$criterion_params],
                  'metacriteria' => []
               ]
            );

            $multi_criteria[] = $criterion_params;

            if (count($multi_criteria) > 50) {
               // Limit criteria count to 50 to prevent performances issues
               // and also prevent exceeding of MySQL join limit.
               break;
            }
         }

         // do a search query with all criteria at the same time
         $search_params = ['is_deleted'   => 0,
                           'start'        => 0,
                           'criteria'     => $multi_criteria,
                           'metacriteria' => []];
         $this->doSearch($class, $search_params);
      }
   }

   /**
    * Test search with all meta to not have SQL errors
    *
    * @return void
    */
   public function testSearchAllMeta() {

      $classes = $this->getSearchableClasses();

      // extract metacriteria
      $itemtype_criteria = [];
      foreach ($classes as $class) {
         $itemtype = $class::getType();
         $itemtype_criteria[$itemtype] = [];
         $metaList = \Search::getMetaItemtypeAvailable($itemtype);
         foreach ($metaList as $metaitemtype) {
            $item = getItemForItemtype($metaitemtype);
            foreach ($item->searchOptions() as $key => $data) {
               if (is_array($data) && array_key_exists('nometa', $data) && $data['nometa'] === true) {
                  continue;
               }
               if (!is_int($key) || ($criterion_params = $this->getCriterionParams($item, $key, $data)) === null) {
                  continue;
               }

               $criterion_params['itemtype'] = $metaitemtype;
               $criterion_params['link'] = 'AND';

               $itemtype_criteria[$itemtype][] = $criterion_params;
            }
         }
      }

      foreach ($itemtype_criteria as $itemtype => $criteria) {
         if (empty($criteria)) {
            continue;
         }

         $first_criteria_by_metatype = [];

         // Search with each meta criteria independently.
         foreach ($criteria as $criterion_params) {
            if (!array_key_exists($criterion_params['itemtype'], $first_criteria_by_metatype)) {
               $first_criteria_by_metatype[$criterion_params['itemtype']] = $criterion_params;
            }

            $search_params = ['is_deleted'   => 0,
                              'start'        => 0,
                              'criteria'     => [0 => ['field'      => 'view',
                                                       'searchtype' => 'contains',
                                                       'value'      => '']],
                              'metacriteria' => [$criterion_params]];
            $this->doSearch($itemtype, $search_params);
         }

         // Search with criteria related to multiple meta items.
         // Limit criteria count to 5 to prevent performances issues (mainly on MariaDB).
         // Test would take hours if done using too many criteria on each request.
         // Thus, using 5 different meta items on a request seems already more than a normal usage.
         foreach (array_chunk($first_criteria_by_metatype, 3) as $criteria_chunk) {
            $search_params = ['is_deleted'   => 0,
                              'start'        => 0,
                              'criteria'     => [0 => ['field'      => 'view',
                                                       'searchtype' => 'contains',
                                                       'value'      => '']],
                              'metacriteria' => $criteria_chunk];
            $this->doSearch($itemtype, $search_params);
         }
      }
   }

   /**
    * Get criterion params for corresponding SO.
    *
    * @param CommonDBTM $item
    * @param int $so_key
    * @param array $so_data
    * @return null|array
    */
   private function getCriterionParams(CommonDBTM $item, int $so_key, array $so_data): ?array {
      global $DB;

      if ((array_key_exists('nosearch', $so_data) && $so_data['nosearch'])) {
         return null;
      }
      $actions = \Search::getActionsFor($item->getType(), $so_key);
      $searchtype = array_keys($actions)[0];

      switch ($so_data['datatype'] ?? null) {
         case 'bool':
         case 'integer':
         case 'number':
            $val = 0;
            break;
         case 'date':
         case 'date_delay':
            $val = date('Y-m-d');
            break;
         case 'datetime':
            // Search class expects seconds to be ":00".
            $val = date('Y-m-d H:i:00');
            break;
         case 'right':
            $val = READ;
            break;
         default:
            if (array_key_exists('table', $so_data) && array_key_exists('field', $so_data)) {
               $field = $DB->tableExists($so_data['table']) ? $DB->getField($so_data['table'], $so_data['field']) : null;
               if (preg_match('/int(\(\d+\))?$/', $field['Type'] ?? '')) {
                  $val = 1;
                  break;
               }
            }

            $val = 'val';
            break;
      }

      return [
         'field'      => $so_key,
         'searchtype' => $searchtype,
         'value'      => $val
      ];
   }

   public function testIsNotifyComputerGroup() {
      $search_params = ['is_deleted'   => 0,
                        'start'        => 0,
                        'search'       => 'Search',
                        'criteria'     => [0 => ['field'      => 'view',
                                                 'searchtype' => 'contains',
                                                 'value'      => '']],
                                                     // group is_notify
                        'metacriteria' => [0 => ['link'       => 'AND',
                                                 'itemtype'   => 'Group',
                                                 'field'      => 20,
                                                 'searchtype' => 'equals',
                                                 'value'      => 1]]];
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $data = $this->doSearch('Computer', $search_params);

      //expecting no result
      $this->integer($data['data']['totalcount'])->isIdenticalTo(0);

      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      //create group that can be notified
      $group = new \Group();
      $gid = $group->add(
         [
            'name'         => '_test_group01',
            'is_notify'    => '1',
            'entities_id'  => $computer1->fields['entities_id'],
            'is_recursive' => 1
         ]
      );
      $this->integer($gid)->isGreaterThan(0);

      //attach group to computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => $gid
         ]
      );
      $this->boolean($updated)->isTrue();

      $data = $this->doSearch('Computer', $search_params);

      //reset computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => 0
         ]
      );
      $this->boolean($updated)->isTrue();

      $this->integer($data['data']['totalcount'])->isIdenticalTo(1);
   }

   public function testDateBeforeOrNot() {
      //tickets created since one week
      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            0 => [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => ''
            ],
            // creation date
            1 => [
               'link'       => 'AND',
               'field'      => '15',
               'searchtype' => 'morethan',
               'value'      => '-1WEEK'
            ]
         ]
      ];

      $data = $this->doSearch('Ticket', $search_params);

      $this->integer($data['data']['totalcount'])->isGreaterThan(1);

      //negate previous search
      $search_params['criteria'][1]['link'] = 'AND NOT';
      $data = $this->doSearch('Ticket', $search_params);

      $this->integer($data['data']['totalcount'])->isIdenticalTo(0);
   }

   /**
    * Test that searchOptions throws an exception when it finds a duplicate
    *
    * @return void
    */
   public function testGetSearchOptionsWException() {
      $error = 'Duplicate key 12 (One search option/Any option) in tests\units\DupSearchOpt searchOptions! ';

      $this->exception(
         function () {
            $item = new DupSearchOpt();
            $item->searchOptions();
         }
      )
         ->isInstanceOf('\RuntimeException')
         ->message->endWith($error);
   }

   function testManageParams() {
      // let's use TU_USER
      $this->login();
      $uid =  getItemByTypeName('User', TU_USER, true);

      $search = \Search::manageParams('Ticket', ['reset' => 1], false, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 19,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => 12,
                                             'searchtype' => 'equals',
                                             'value' => 'notold'
                                            ],
                                      ],
                    'metacriteria' => [],
                    'as_map'       => 0
                   ]);

      // now add a bookmark on Ticket view
      $bk = new \SavedSearch();
      $this->boolean(
         (boolean)$bk->add(['name'         => 'All my tickets',
                            'type'         => 1,
                            'itemtype'     => 'Ticket',
                            'users_id'     => $uid,
                            'is_private'   => 1,
                            'entities_id'  => 0,
                            'is_recursive' => 1,
                            'url'         => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]='.$uid
                           ])
      )->isTrue();

      $bk_id = $bk->fields['id'];

      $bk_user = new \SavedSearch_User();
      $this->boolean(
         (boolean)$bk_user->add(['users_id' => $uid,
                                 'itemtype' => 'Ticket',
                                 'savedsearches_id' => $bk_id
                                ])
      )->isTrue();

      $search = \Search::manageParams('Ticket', ['reset' => 1], true, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 2,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => '5',
                                             'searchtype' => 'equals',
                                             'value' => $uid
                                            ],
                                      ],
                    'metacriteria' => [],
                    'itemtype' => 'Ticket',
                    'savedsearches_id' => $bk_id,
                    'as_map'           => 0
                   ]);

      // let's test for Computers
      $search = \Search::manageParams('Computer', ['reset' => 1], false, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'ASC',
                    'sort'         => 1,
                    'is_deleted'   => 0,
                    'criteria'     => [
                        0 => [
                           'field' => 'view',
                           'link'  => 'contains',
                           'value' => '',
                        ]
                     ],
                    'metacriteria' => [],
                    'as_map'       => 0
                   ]);

      // now add a bookmark on Computer view
      $bk = new \SavedSearch();
      $this->boolean(
         (boolean)$bk->add(['name'         => 'Computer test',
                            'type'         => 1,
                            'itemtype'     => 'Computer',
                            'users_id'     => $uid,
                            'is_private'   => 1,
                            'entities_id'  => 0,
                            'is_recursive' => 1,
                            'url'         => 'front/computer.php?itemtype=Computer&sort=31&order=DESC&criteria%5B0%5D%5Bfield%5D=view&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=test'
                           ])
      )->isTrue();

      $bk_id = $bk->fields['id'];

      $bk_user = new \SavedSearch_User();
      $this->boolean(
         (boolean)$bk_user->add(['users_id' => $uid,
                                 'itemtype' => 'Computer',
                                 'savedsearches_id' => $bk_id
                                ])
      )->isTrue();

      $search = \Search::manageParams('Computer', ['reset' => 1], true, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 31,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => 'view',
                                             'searchtype' => 'contains',
                                             'value' => 'test'
                                            ],
                                      ],
                    'metacriteria' => [],
                    'itemtype' => 'Computer',
                    'savedsearches_id' => $bk_id,
                    'as_map'           => 0
                   ]);

   }

   public function addSelectProvider() {
      return [
         'special_fk' => [[
            'itemtype'  => 'Computer',
            'ID'        => 24, // users_id_tech
            'sql'       => '`glpi_users_users_id_tech`.`name` AS `ITEM_Computer_24`, `glpi_users_users_id_tech`.`realname` AS `ITEM_Computer_24_realname`,
                           `glpi_users_users_id_tech`.`id` AS `ITEM_Computer_24_id`, `glpi_users_users_id_tech`.`firstname` AS `ITEM_Computer_24_firstname`,'
         ]],
         'regular_fk' => [[
            'itemtype'  => 'Computer',
            'ID'        => 70, // users_id
            'sql'       => '`glpi_users`.`name` AS `ITEM_Computer_70`, `glpi_users`.`realname` AS `ITEM_Computer_70_realname`,
                           `glpi_users`.`id` AS `ITEM_Computer_70_id`, `glpi_users`.`firstname` AS `ITEM_Computer_70_firstname`,'
         ]],
      ];
   }

   /**
    * @dataProvider addSelectProvider
    */
   public function testAddSelect($provider) {
      $sql_select = \Search::addSelect($provider['itemtype'], $provider['ID']);

      $this->string($this->cleanSQL($sql_select))
         ->isEqualTo($this->cleanSQL($provider['sql']));
   }

   public function addLeftJoinProvider() {
      return [
         'itemtype_item_revert' => [[
            'itemtype'           => 'Project',
            'table'              => \Contact::getTable(),
            'field'              => 'name',
            'linkfield'          => 'id',
            'meta'               => false,
            'meta_type'          => null,
            'joinparams'         => [
               'jointype'          => 'itemtype_item_revert',
               'specific_itemtype' => 'Contact',
               'beforejoin'        => [
                  'table'      => \ProjectTeam::getTable(),
                  'joinparams' => [
                     'jointype' => 'child',
                  ]
               ]
            ],
            'sql' => "LEFT JOIN `glpi_projectteams`
                        ON (`glpi_projects`.`id` = `glpi_projectteams`.`projects_id`
                            )
                      LEFT JOIN `glpi_contacts`  AS `glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`
                        ON (`glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`.`id` = `glpi_projectteams`.`items_id`
                        AND `glpi_projectteams`.`itemtype` = 'Contact'
                         )"
         ]],
         'special_fk' => [[
            'itemtype'           => 'Computer',
            'table'              => \User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'meta'               => false,
            'meta_type'          => null,
            'joinparams'         => [],
            'sql' => "LEFT JOIN `glpi_users` AS `glpi_users_users_id_tech` ON (`glpi_computers`.`users_id_tech` = `glpi_users_users_id_tech`.`id` )"
         ]],
         'regular_fk' => [[
            'itemtype'           => 'Computer',
            'table'              => \User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'meta'               => false,
            'meta_type'          => null,
            'joinparams'         => [],
            'sql' => "LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id` )"
         ]],
      ];
   }

   /**
    * @dataProvider addLeftJoinProvider
    */
   public function testAddLeftJoin($lj_provider) {
      $already_link_tables = [];

      $sql_join = \Search::addLeftJoin(
         $lj_provider['itemtype'],
         getTableForItemType($lj_provider['itemtype']),
         $already_link_tables,
         $lj_provider['table'],
         $lj_provider['linkfield'],
         $lj_provider['meta'],
         $lj_provider['meta_type'],
         $lj_provider['joinparams'],
         $lj_provider['field']
      );

      $this->string($this->cleanSQL($sql_join))
           ->isEqualTo($this->cleanSQL($lj_provider['sql']));
   }

   protected function addOrderByBCProvider(): array {
      return [
         // Generic examples
         [
            'Computer', 5, 'ASC',
            ' ORDER BY `ITEM_Computer_5` ASC '
         ],
         [
            'Computer', 5, 'DESC',
            ' ORDER BY `ITEM_Computer_5` DESC '
         ],
         [
            'Computer', 5, 'INVALID',
            ' ORDER BY `ITEM_Computer_5` DESC '
         ],
         // Simple Hard-coded cases
         [
            'IPAddress', 1, 'ASC',
            ' ORDER BY INET_ATON(`glpi_ipaddresses`.`name`) ASC '
         ],
         [
            'IPAddress', 1, 'DESC',
            ' ORDER BY INET_ATON(`glpi_ipaddresses`.`name`) DESC '
         ],
         [
            'User', 1, 'ASC',
            ' ORDER BY `glpi_users`.`name` ASC '
         ],
         [
            'User', 1, 'DESC',
            ' ORDER BY `glpi_users`.`name` DESC '
         ],
      ];
   }

   protected function addOrderByProvider(): array {
      return [
         // Generic examples
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
                  'order'        => 'ASC'
               ]
            ], ' ORDER BY `ITEM_Computer_5` ASC '
         ],
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
                  'order'        => 'DESC'
               ]
            ], ' ORDER BY `ITEM_Computer_5` DESC '
         ],
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
                  'order'        => 'INVALID'
               ]
            ], ' ORDER BY `ITEM_Computer_5` DESC '
         ],
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
               ]
            ], ' ORDER BY `ITEM_Computer_5` ASC '
         ],
         // Simple Hard-coded cases
         [
            'IPAddress',
            [
               [
                  'searchopt_id' => 1,
                  'order'        => 'ASC'
               ]
            ], ' ORDER BY INET_ATON(`glpi_ipaddresses`.`name`) ASC '
         ],
         [
            'IPAddress',
            [
               [
                  'searchopt_id' => 1,
                  'order'        => 'DESC'
               ]
            ], ' ORDER BY INET_ATON(`glpi_ipaddresses`.`name`) DESC '
         ],
         [
            'User',
            [
               [
                  'searchopt_id' => 1,
                  'order'        => 'ASC'
               ]
            ], ' ORDER BY `glpi_users`.`name` ASC '
         ],
         [
            'User',
            [
               [
                  'searchopt_id' => 1,
                  'order'        => 'DESC'
               ]
            ], ' ORDER BY `glpi_users`.`name` DESC '
         ],
         // Multiple sort cases
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
                  'order'        => 'ASC'
               ],
               [
                  'searchopt_id' => 6,
                  'order'        => 'ASC'
               ],
            ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` ASC '
         ],
         [
            'Computer',
            [
               [
                  'searchopt_id' => 5,
                  'order'        => 'ASC'
               ],
               [
                  'searchopt_id' => 6,
                  'order'        => 'DESC'
               ],
            ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` DESC '
         ],
      ];
   }

   /**
    * @dataProvider addOrderByBCProvider
    */
   public function testAddOrderByBC($itemtype, $id, $order, $expected) {
      $result = null;
      $this->when(
         function () use (&$result, $itemtype, $id, $order) {
            $result = \Search::addOrderBy($itemtype, $id, $order);
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
      $this->string($result)->isEqualTo($expected);

      // Complex cases
      $table_addtable = 'glpi_users_af1042e23ce6565cfe58c6db91f84692';

      $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
      $user_order_1 = null;
      $this->when(
         function () use (&$user_order_1) {
            $user_order_1 = \Search::addOrderBy('Ticket', 4, 'ASC');
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
      $this->string($user_order_1)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`name` ASC ");

      $user_order_2 = null;
      $this->when(
         function () use (&$user_order_2) {
            $user_order_2 = \Search::addOrderBy('Ticket', 4, 'DESC');
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
      $this->string($user_order_2)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`name` DESC ");

      $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
      $user_order_3 = null;
      $this->when(
         function () use (&$user_order_3) {
            $user_order_3 = \Search::addOrderBy('Ticket', 4, 'ASC');
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
      $this->string($user_order_3)->isEqualTo(" ORDER BY `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`name` ASC ");
      $user_order_4 = null;
      $this->when(
         function () use (&$user_order_4) {
            $user_order_4 = \Search::addOrderBy('Ticket', 4, 'DESC');
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
      $this->string($user_order_4)->isEqualTo(" ORDER BY `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`name` DESC ");
   }

   /**
    * @dataProvider addOrderByProvider
    */
   public function testAddOrderBy($itemtype, $sort_fields, $expected) {
      $result = \Search::addOrderBy($itemtype, $sort_fields);
      $this->string($result)->isEqualTo($expected);

      // Complex cases
      $table_addtable = 'glpi_users_af1042e23ce6565cfe58c6db91f84692';

      $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
      $user_order_1 = \Search::addOrderBy('Ticket', [
         [
            'searchopt_id' => 4,
            'order'        => 'ASC'
         ]
      ]);
      $this->string($user_order_1)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`name` ASC ");
      $user_order_2 = \Search::addOrderBy('Ticket', [
         [
            'searchopt_id' => 4,
            'order'        => 'DESC'
         ]
      ]);
      $this->string($user_order_2)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`name` DESC ");

      $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
      $user_order_3 = \Search::addOrderBy('Ticket', [
         [
            'searchopt_id' => 4,
            'order'        => 'ASC'
         ]
      ]);
      $this->string($user_order_3)->isEqualTo(" ORDER BY `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`name` ASC ");
      $user_order_4 = \Search::addOrderBy('Ticket', [
         [
            'searchopt_id' => 4,
            'order'        => 'DESC'
         ]
      ]);
      $this->string($user_order_4)->isEqualTo(" ORDER BY `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`name` DESC ");
   }

   private function cleanSQL($sql) {
      $sql = str_replace("\r\n", ' ', $sql);
      $sql = str_replace("\n", ' ', $sql);
      while (strpos($sql, '  ') !== false) {
         $sql = str_replace('  ', ' ', $sql);
      }

      $sql = trim($sql);

      return $sql;
   }

   public function testAllAssetsFields() {
      global $CFG_GLPI, $DB;

      $needed_fields = [
         'id',
         'name',
         'states_id',
         'locations_id',
         'serial',
         'otherserial',
         'comment',
         'users_id',
         'contact',
         'contact_num',
         'groups_id',
         'date_mod',
         'manufacturers_id',
         'groups_id_tech',
         'entities_id',
      ];

      foreach ($CFG_GLPI["asset_types"] as $itemtype) {
         $table = getTableForItemType($itemtype);

         foreach ($needed_fields as $field) {
            $this->boolean($DB->fieldExists($table, $field))
                 ->isTrue("$table.$field is missing");
         }
      }
   }

   public function testProblems() {
      $tech_users_id = getItemByTypeName('User', "tech", true);

      // reduce the right of tech profile
      // to have only the right of display their own problems (created, assign)
      \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
         'Problem' => (\Problem::READMY + READNOTE + UPDATENOTE)
      ]);

      // add a group for tech user
      $group = new \Group;
      $groups_id = $group->add([
         'name' => "test group for tech user"
      ]);
      $this->integer((int)$groups_id)->isGreaterThan(0);
      $group_user = new \Group_User;
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $tech_users_id
         ])
      )->isGreaterThan(0);

      // create a problem and assign group with tech user
      $problem = new \Problem;
      $this->integer(
         (int)$problem->add([
            'name'              => "test problem visibility for tech",
            'content'           => "test problem visibility for tech",
            '_groups_id_assign' => $groups_id
         ])
      )->isGreaterThan(0);

      // let's use tech user
      $this->login('tech', 'tech');

      // do search and check presence of the created problem
      $data = \Search::prepareDatasForSearch('Problem', ['reset' => 'reset']);
      \Search::constructSQL($data);
      \Search::constructData($data);

      $this->integer($data['data']['totalcount'])->isEqualTo(1);
      $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_Problem_1']->isEqualTo('test problem visibility for tech');

   }

   public function testChanges() {
      $tech_users_id = getItemByTypeName('User', "tech", true);

      // reduce the right of tech profile
      // to have only the right of display their own changes (created, assign)
      \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
         'Change' => (\Change::READMY + READNOTE + UPDATENOTE)
      ]);

      // add a group for tech user
      $group = new \Group;
      $groups_id = $group->add([
         'name' => "test group for tech user"
      ]);
      $this->integer((int)$groups_id)->isGreaterThan(0);

      $group_user = new \Group_User;
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $tech_users_id
         ])
      )->isGreaterThan(0);

      // create a Change and assign group with tech user
      $change = new \Change;
      $this->integer(
         (int)$change->add([
            'name'              => "test Change visibility for tech",
            'content'           => "test Change visibility for tech",
            '_groups_id_assign' => $groups_id
         ])
      )->isGreaterThan(0);

      // let's use tech user
      $this->login('tech', 'tech');

      // do search and check presence of the created Change
      $data = \Search::prepareDatasForSearch('Change', ['reset' => 'reset']);
      \Search::constructSQL($data);
      \Search::constructData($data);

      $this->integer($data['data']['totalcount'])->isEqualTo(1);
      $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_Change_1']->isEqualTo('test Change visibility for tech');

   }

   public function testSearchDdTranslation() {
      global $CFG_GLPI;

      $this->login();
      $conf = new \Config();
      $conf->setConfigurationValues('core', ['translate_dropdowns' => 1]);
      $CFG_GLPI['translate_dropdowns'] = 1;

      $state = new \State();
      $this->boolean($state->maybeTranslated())->isTrue();

      $sid = $state->add([
         'name'         => 'A test state',
         'is_recursive' => 1
      ]);
      $this->integer($sid)->isGreaterThan(0);

      $ddtrans = new \DropdownTranslation();
      $this->integer(
         $ddtrans->add([
            'itemtype'  => $state->getType(),
            'items_id'  => $state->fields['id'],
            'language'  => 'fr_FR',
            'field'     => 'completename',
            'value'     => 'Un status de test'
         ])
      )->isGreaterThan(0);

      $_SESSION['glpi_dropdowntranslations'] = [$state->getType() => ['completename' => '']];

      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            0 => [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => 'test'
            ]
         ],
         'metacriteria' => []
      ];

      $data = $this->doSearch('State', $search_params);

      $this->integer($data['data']['totalcount'])->isIdenticalTo(1);

      $conf->setConfigurationValues('core', ['translate_dropdowns' => 0]);
      $CFG_GLPI['translate_dropdowns'] = 0;
      unset($_SESSION['glpi_dropdowntranslations']);
   }

   public function dataInfocomOptions() {
      return [
         [1, false],
         [2, false],
         [4, false],
         [40, false],
         [31, false],
         [80, false],
         [25, true],
         [26, true],
         [27, true],
         [28, true],
         [37, true],
         [38, true],
         [50, true],
         [51, true],
         [52, true],
         [53, true],
         [54, true],
         [55, true],
         [56, true],
         [57, true],
         [58, true],
         [59, true],
         [120, true],
         [122, true],
         [123, true],
         [124, true],
         [125, true],
         [142, true],
         [159, true],
         [173, true],
      ];
   }

   /**
    * @dataProvider dataInfocomOptions
    */
   public function testIsInfocomOption($index, $expected) {
      $this->boolean(\Search::isInfocomOption('Computer', $index))->isIdenticalTo($expected);
   }

   protected function makeTextSearchValueProvider() {
      return [
         ['NULL', null],
         ['null', null],
         ['', ''],
         ['^', '%'],
         ['$', ''],
         ['^$', ''],
         ['$^', '%$^%'], // inverted ^ and $
         ['looking for', '%looking for%'],
         ['^starts with', 'starts with%'],
         ['ends with$', '%ends with'],
         ['^exact string$', 'exact string'],
         ['a ^ in the middle$', '%a ^ in the middle'],
         ['^and $ not at the end', 'and $ not at the end%'],
         ['45$^ab5', '%45$^ab5%'],
         ['^ ltrim', 'ltrim%'],
         ['rtim this   $', '%rtim this'],
         ['  extra spaces ', '%extra spaces%'],
         ['^ exactval $', 'exactval'],
      ];
   }

   /**
    * @dataProvider makeTextSearchValueProvider
    */
   public function testMakeTextSearchValue($value, $expected) {
      $this->variable(\Search::makeTextSearchValue($value))->isIdenticalTo($expected);
   }

   public function providerAddWhere() {
      return [
         [
            'link' => ' ',
            'nott' => 0,
            'itemtype' => \User::class,
            'ID' => 99,
            'searchtype' => 'equals',
            'val' => '5',
            'meta' => false,
            'expected' => "   (`glpi_users_users_id_supervisor`.`id` = '5')",
         ],
         [
            'link' => ' AND ',
            'nott' => 0,
            'itemtype' => \CartridgeItem::class,
            'ID' => 24,
            'searchtype' => 'equals',
            'val' => '2',
            'meta' => false,
            'expected' => "  AND  (`glpi_users_users_id_tech`.`id` = '2') ",
         ],
      ];
   }

   /**
    * @dataProvider providerAddWhere
    */
   public function testAddWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta, $expected) {
      $output = \Search::addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta);
      $this->string($output)->isEqualTo($expected);

      if ($meta) {
         return; // Do not know how to run search on meta here
      }

      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            [
               'field'      => $ID,
               'searchtype' => $searchtype,
               'value'      => $val
            ]
         ],
         'metacriteria' => []
      ];

      // Run a search to trigger a test failure if anything goes wrong.
      $this->doSearch($itemtype, $search_params);
   }

   public function testSearchWGroups() {
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $search_params = ['is_deleted'   => 0,
                        'start'        => 0,
                        'search'       => 'Search',
                        'criteria'     => [0 => ['field'      => 'view',
                                                 'searchtype' => 'contains',
                                                 'value'      => 'pc']]];
      $data = $this->doSearch('Computer', $search_params);

      $this->integer($data['data']['totalcount'])->isIdenticalTo(8);

      $displaypref = new \DisplayPreference();
      $input = [
            'itemtype'  => 'Computer',
            'users_id'  => \Session::getLoginUserID(),
            'num'       => 49, //Computer groups_id_tech SO
      ];
      $this->integer((int)$displaypref->add($input))->isGreaterThan(0);

      $data = $this->doSearch('Computer', $search_params);

      $this->integer($data['data']['totalcount'])->isIdenticalTo(8);
   }

   public function testSearchWithMultipleFkeysOnSameTable() {
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $user_tech_id   = getItemByTypeName('User', 'tech', true);
      $user_normal_id = getItemByTypeName('User', 'normal', true);

      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'sort'         => 22,
         'order'        => 'ASC',
         'search'       => 'Search',
         'criteria'     => [
            0 => [
               'link'       => 'AND',
               'field'      => '64', // Last updater
               'searchtype' => 'equals',
               'value'      => $user_tech_id,
            ],
            1 => [
               'link'       => 'AND',
               'field'      => '22', // Recipient
               'searchtype' => 'equals',
               'value'      => $user_normal_id,
            ]
         ]
      ];
      $data = $this->doSearch('Ticket', $search_params);

      $this->string($data['sql']['search'])
         // Check that we have two different joins
         ->contains("LEFT JOIN `glpi_users`  AS `glpi_users_users_id_lastupdater`")
         ->contains("LEFT JOIN `glpi_users`  AS `glpi_users_users_id_recipient`")

         // Check that SELECT criteria applies on corresponding table alias
         ->contains("`glpi_users_users_id_lastupdater`.`realname` AS `ITEM_Ticket_64_realname`")
         ->contains("`glpi_users_users_id_recipient`.`realname` AS `ITEM_Ticket_22_realname`")

         // Check that WHERE criteria applies on corresponding table alias
         ->contains("`glpi_users_users_id_lastupdater`.`id` = '{$user_tech_id}'")
         ->contains("`glpi_users_users_id_recipient`.`id` = '{$user_normal_id}'")

         // Check that ORDER applies on corresponding table alias
         ->contains("`glpi_users_users_id_recipient`.`name` ASC");
   }

   function testSearchAllAssets() {
      $data = $this->doSearch('AllAssets', [
         'reset'      => 'reset',
         'is_deleted' => 0,
         'start'      => 0,
         'search'     => 'Search',
         'criteria'   => [
            [
               'link'       => 'AND',
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => 'test',
            ],
         ]
      ]);

      $this->string($data['sql']['search'])
         ->matches("/OR\s*\(`glpi_entities`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_states`\.`completename`\s*LIKE '%test%'\s*\)/");

      $types = [
         \Computer::getTable(),
         \Monitor::getTable(),
         \NetworkEquipment::getTable(),
         \Peripheral::getTable(),
         \Phone::getTable(),
         \Printer::getTable(),
      ];

      foreach ($types as $type) {
         $this->string($data['sql']['search'])
            ->contains("`$type`.`is_deleted` = 0")
            ->contains("AND `$type`.`is_template` = 0")
            ->contains("`$type`.`entities_id` IN ('1', '2', '3')")
            ->contains("OR (`$type`.`is_recursive`='1'".
                        " AND `$type`.`entities_id` IN (0))")
            ->matches("/`$type`\.`name`  LIKE '%test%'/");
      }
   }

   public function testSearchWithNamespacedItem() {
      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'search'       => 'Search',
      ];
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $data = $this->doSearch('SearchTest\\Computer', $search_params);

      $this->string($data['sql']['search'])
         ->contains("`glpi_computers`.`name` AS `ITEM_SearchTest\Computer_1`")
         ->contains("`glpi_computers`.`id` AS `ITEM_SearchTest\Computer_1_id`")
         ->contains("ORDER BY `ITEM_SearchTest\Computer_1` ASC");
   }

   /**
    * Check that search result is valid.
    *
    * @param array $result
    */
   private function checkSearchResult($result) {
      $this->array($result)->hasKey('data');
      $this->array($result['data'])->hasKeys(['count', 'begin', 'end', 'totalcount', 'cols', 'rows', 'items']);
      $this->integer($result['data']['count']);
      $this->integer($result['data']['begin']);
      $this->integer($result['data']['end']);
      $this->integer($result['data']['totalcount']);
      $this->array($result['data']['cols']);
      $this->array($result['data']['rows']);
      $this->array($result['data']['items']);

      // No errors
      $this->array($result)->hasKey('last_errors');
      $this->array($result['last_errors'])->isIdenticalTo([]);

      $this->array($result)->hasKey('sql');
      $this->array($result['sql'])->hasKey('search');
      $this->string($result['sql']['search']);
   }

   /**
    * Returns list of searchable classes.
    *
    * @return array
    */
   private function getSearchableClasses(): array {
      $classes = $this->getClasses(
         'searchOptions',
         [
            '/^Common.*/', // Should be abstract
            'NetworkPortInstantiation', // Should be abstract (or have $notable = true)
            'NetworkPortMigration', // Tables only exists in specific cases
            'NotificationSettingConfig', // Stores its data in glpi_configs, does not acts as a CommonDBTM
         ]
      );
      $searchable_classes = [];
      foreach ($classes as $class) {
         $item_class = new \ReflectionClass($class);
         if ($item_class->isAbstract() || $class::getTable() === '' || !is_a($class, CommonDBTM::class, true)) {
            // abstract class or class with "static protected $notable = true;" (which is a kind of abstract)
            continue;
         }

         $searchable_classes[] = $class;
      }
      sort($searchable_classes);

      return $searchable_classes;
   }

   protected function testNamesOutputProvider(): array {
      return [
         [
            'params' => [
               'display_type' => \Search::NAMES_OUTPUT,
               'export_all'   => 1,
               'criteria'     => [],
               'item_type'    => 'Ticket',
               'is_deleted'   => 0,
               'as_map'       => 0,
            ],
            'expected' => [
               '_ticket01',
               '_ticket02',
               '_ticket03',
               '_ticket100',
               '_ticket101',
            ]
         ],
         [
            'params' => [
               'display_type' => \Search::NAMES_OUTPUT,
               'export_all'   => 1,
               'criteria'     => [],
               'item_type'    => 'Computer',
               'is_deleted'   => 0,
               'as_map'       => 0,
            ],
            'expected' => [
               '_test_pc01',
               '_test_pc02',
               '_test_pc03',
               '_test_pc11',
               '_test_pc12',
               '_test_pc13',
               '_test_pc21',
               '_test_pc22',
            ]
         ],
      ];
   }

   /**
    * @dataProvider testNamesOutputProvider
    */
   public function testNamesOutput(array $params, array $expected) {
      $this->login();

      // Run search and capture results
      ob_start();
      \Search::showList($params['item_type'], $params);
      $names = ob_get_contents();
      ob_end_clean();

      // Convert results to array and remove last row (always empty)
      $names = explode("\n", $names);
      array_pop($names);

      // Check results
      $this->array($names)->isEqualTo($expected);
   }
}

class DupSearchOpt extends \CommonDBTM {
   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'     => '12',
         'name'   => 'One search option'
      ];

      $tab[] = [
         'id'     => '12',
         'name'   => 'Any option'
      ];

      return $tab;
   }
}

// phpcs:ignore SlevomatCodingStandard.Namespaces
namespace SearchTest;

class Computer extends \Computer {
   static function getTable($classname = null) {
      return 'glpi_computers';
   }
}
