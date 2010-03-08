<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Update from 0.72.3 to 0.78
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
 */
function update0723to078($output='HTML') {
	global $DB, $LANG;

   $updateresult = true;

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.78</h3>";
   }
   displayMigrationMessage("078"); // Start

   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : rename tables'); // Updating schema

   $changes=array();
   $glpi_tables=array(
      'glpi_alerts'                       => 'glpi_alerts',
      'glpi_auth_ldap'                    => 'glpi_authldaps',
      'glpi_auth_ldap_replicate'          => 'glpi_authldapreplicates',
      'glpi_auth_mail'                    => 'glpi_authmails',
      'glpi_dropdown_auto_update'         => 'glpi_autoupdatesystems',
      'glpi_bookmark'                     => 'glpi_bookmarks',
      'glpi_display_default'              => 'glpi_bookmarks_users',
      'glpi_dropdown_budget'              => 'glpi_budgets',
      'glpi_cartridges'                   => 'glpi_cartridges',
      'glpi_cartridges_type'              => 'glpi_cartridgeitems',
      'glpi_cartridges_assoc'             => 'glpi_cartridges_printermodels',
      'glpi_dropdown_cartridge_type'      => 'glpi_cartridgeitemtypes',
      'glpi_computers'                    => 'glpi_computers',
      'glpi_computerdisks'                => 'glpi_computerdisks',
      'glpi_dropdown_model'               => 'glpi_computermodels',
      'glpi_type_computers'               => 'glpi_computertypes',
      'glpi_connect_wire'                 => 'glpi_computers_items',
      'glpi_inst_software'                => 'glpi_computers_softwareversions',
      'glpi_config'                       => 'glpi_configs',
      'glpi_consumables'                  => 'glpi_consumables',
      'glpi_consumables_type'             => 'glpi_consumableitems',
      'glpi_dropdown_consumable_type'     => 'glpi_consumableitemtypes',
      'glpi_contact_enterprise'           => 'glpi_contacts_suppliers',
      'glpi_contacts'                     => 'glpi_contacts',
      'glpi_dropdown_contact_type'        => 'glpi_contacttypes',
      'glpi_contracts'                    => 'glpi_contracts',
      'glpi_dropdown_contract_type'       => 'glpi_contracttypes',
      'glpi_contract_device'              => 'glpi_contracts_items',
      'glpi_contract_enterprise'          => 'glpi_contracts_suppliers',
      'glpi_device_case'                  => 'glpi_devicecases',
      'glpi_dropdown_case_type'           => 'glpi_devicecasetypes',
      'glpi_device_control'               => 'glpi_devicecontrols',
      'glpi_device_drive'                 => 'glpi_devicedrives',
      'glpi_device_gfxcard'               => 'glpi_devicegraphiccards',
      'glpi_device_hdd'                   => 'glpi_deviceharddrives',
      'glpi_device_iface'                 => 'glpi_devicenetworkcards',
      'glpi_device_moboard'               => 'glpi_devicemotherboards',
      'glpi_device_pci'                   => 'glpi_devicepcis',
      'glpi_device_power'                 => 'glpi_devicepowersupplies',
      'glpi_device_processor'             => 'glpi_deviceprocessors',
      'glpi_device_ram'                   => 'glpi_devicememories',
      'glpi_dropdown_ram_type'            => 'glpi_devicememorytypes',
      'glpi_device_sndcard'               => 'glpi_devicesoundcards',
      'glpi_display'                      => 'glpi_displaypreferences',
      'glpi_docs'                         => 'glpi_documents',
      'glpi_dropdown_rubdocs'             => 'glpi_documentcategories',
      'glpi_type_docs'                    => 'glpi_documenttypes',
      'glpi_doc_device'                   => 'glpi_documents_items',
      'glpi_dropdown_domain'              => 'glpi_domains',
      'glpi_entities'                     => 'glpi_entities',
      'glpi_entities_data'                => 'glpi_entitydatas',
      'glpi_event_log'                    => 'glpi_events',
      'glpi_dropdown_filesystems'         => 'glpi_filesystems',
      'glpi_groups'                       => 'glpi_groups',
      'glpi_users_groups'                 => 'glpi_groups_users',
      'glpi_infocoms'                     => 'glpi_infocoms',
      'glpi_dropdown_interface'           => 'glpi_interfacetypes',
      'glpi_kbitems'                      => 'glpi_knowbaseitems',
      'glpi_dropdown_kbcategories'        => 'glpi_knowbaseitemcategories',
      'glpi_links'                        => 'glpi_links',
      'glpi_links_device'                 => 'glpi_links_itemtypes',
      'glpi_dropdown_locations'           => 'glpi_locations',
      'glpi_history'                      => 'glpi_logs',
      'glpi_mailgate'                     => 'glpi_mailcollectors',
      'glpi_mailing'                      => 'glpi_mailingsettings',
      'glpi_dropdown_manufacturer'        => 'glpi_manufacturers',
      'glpi_monitors'                     => 'glpi_monitors',
      'glpi_dropdown_model_monitors'      => 'glpi_monitormodels',
      'glpi_type_monitors'                => 'glpi_monitortypes',
      'glpi_dropdown_netpoint'            => 'glpi_netpoints',
      'glpi_networking'                   => 'glpi_networkequipments',
      'glpi_dropdown_firmware'            => 'glpi_networkequipmentfirmwares',
      'glpi_dropdown_model_networking'    => 'glpi_networkequipmentmodels',
      'glpi_type_networking'              => 'glpi_networkequipmenttypes',
      'glpi_dropdown_iface'               => 'glpi_networkinterfaces',
      'glpi_networking_ports'             => 'glpi_networkports',
      'glpi_networking_vlan'              => 'glpi_networkports_vlans',
      'glpi_networking_wire'              => 'glpi_networkports_networkports',
      'glpi_dropdown_network'             => 'glpi_networks',
      'glpi_ocs_admin_link'               => 'glpi_ocsadmininfoslinks',
      'glpi_ocs_link'                     => 'glpi_ocslinks',
      'glpi_ocs_config'                   => 'glpi_ocsservers',
      'glpi_dropdown_os'                  => 'glpi_operatingsystems',
      'glpi_dropdown_os_sp'               => 'glpi_operatingsystemservicepacks',
      'glpi_dropdown_os_version'          => 'glpi_operatingsystemversions',
      'glpi_peripherals'                  => 'glpi_peripherals',
      'glpi_dropdown_model_peripherals'   => 'glpi_peripheralmodels',
      'glpi_type_peripherals'             => 'glpi_peripheraltypes',
      'glpi_phones'                       => 'glpi_phones',
      'glpi_dropdown_model_phones'        => 'glpi_phonemodels',
      'glpi_dropdown_phone_power'         => 'glpi_phonepowersupplies',
      'glpi_type_phones'                  => 'glpi_phonetypes',
      'glpi_plugins'                      => 'glpi_plugins',
      'glpi_printers'                     => 'glpi_printers',
      'glpi_dropdown_model_printers'      => 'glpi_printermodels',
      'glpi_type_printers'                => 'glpi_printertypes',
      'glpi_profiles'                     => 'glpi_profiles',
      'glpi_users_profiles'               => 'glpi_profiles_users',
      'glpi_registry'                     => 'glpi_registrykeys',
      'glpi_reminder'                     => 'glpi_reminders',
      'glpi_reservation_resa'             => 'glpi_reservations',
      'glpi_reservation_item'             => 'glpi_reservationitems',
      'glpi_rules_descriptions'           => 'glpi_rules',
      'glpi_rules_actions'                => 'glpi_ruleactions',
      'glpi_rule_cache_model_computer'    => 'glpi_rulecachecomputermodels',
      'glpi_rule_cache_type_computer'     => 'glpi_rulecachecomputertypes',
      'glpi_rule_cache_manufacturer'      => 'glpi_rulecachemanufacturers',
      'glpi_rule_cache_model_monitor'     => 'glpi_rulecachemonitormodels',
      'glpi_rule_cache_type_monitor'      => 'glpi_rulecachemonitortypes',
      'glpi_rule_cache_model_networking'  => 'glpi_rulecachenetworkequipmentmodels',
      'glpi_rule_cache_type_networking'   => 'glpi_rulecachenetworkequipmenttypes',
      'glpi_rule_cache_os'                => 'glpi_rulecacheoperatingsystems',
      'glpi_rule_cache_os_sp'             => 'glpi_rulecacheoperatingsystemservicepacks',
      'glpi_rule_cache_os_version'        => 'glpi_rulecacheoperatingsystemversions',
      'glpi_rule_cache_model_peripheral'  => 'glpi_rulecacheperipheralmodels',
      'glpi_rule_cache_type_peripheral'   => 'glpi_rulecacheperipheraltypes',
      'glpi_rule_cache_model_phone'       => 'glpi_rulecachephonemodels',
      'glpi_rule_cache_type_phone'        => 'glpi_rulecachephonetypes',
      'glpi_rule_cache_model_printer'     => 'glpi_rulecacheprintermodels',
      'glpi_rule_cache_type_printer'      => 'glpi_rulecacheprintertypes',
      'glpi_rule_cache_software'          => 'glpi_rulecachesoftwares',
      'glpi_rules_criterias'              => 'glpi_rulecriterias',
      'glpi_rules_ldap_parameters'        => 'glpi_ruleldapparameters',
      'glpi_software'                     => 'glpi_softwares',
      'glpi_dropdown_software_category'   => 'glpi_softwarecategories',
      'glpi_softwarelicenses'             => 'glpi_softwarelicenses',
      'glpi_dropdown_licensetypes'        => 'glpi_softwarelicensetypes',
      'glpi_softwareversions'             => 'glpi_softwareversions',
      'glpi_dropdown_state'               => 'glpi_states',
      'glpi_enterprises'                  => 'glpi_suppliers',
      'glpi_dropdown_enttype'             => 'glpi_suppliertypes',
      'glpi_tracking'                     => 'glpi_tickets',
      'glpi_dropdown_tracking_category'   => 'glpi_ticketcategories',
      'glpi_followups'                    => 'glpi_ticketfollowups',
      'glpi_tracking_planning'            => 'glpi_ticketplannings',
      'glpi_transfers'                    => 'glpi_transfers',
      'glpi_users'                        => 'glpi_users',
      'glpi_dropdown_user_titles'         => 'glpi_usertitles',
      'glpi_dropdown_user_types'          => 'glpi_usercategories',
      'glpi_dropdown_vlan'                => 'glpi_vlans',
   );
   $backup_tables=false;
	foreach ($glpi_tables as $original_table => $new_table) {
      if (strcmp($original_table,$new_table)!=0) {
         // Original table exists ?
         if (TableExists($original_table)) {
            // rename new tables if exists ?
            if (TableExists($new_table)) {
               if (TableExists("backup_$new_table")) {
                  $query="DROP TABLE `backup_".$new_table."`";
                  $DB->query($query) or die("0.78 drop backup table backup_$new_table ". $LANG['update'][90] . $DB->error());
               }
               if ($output) {
                  echo "<p><b>$new_table table already exists. ";
                  echo "A backup have been done to backup_$new_table.</b></p>";
               }
               $backup_tables=true;
               $query="RENAME TABLE `$new_table` TO `backup_$new_table`";
               $DB->query($query) or die("0.78 backup table $new_table " . $LANG['update'][90] . $DB->error());

            }
            // rename original table
            $query="RENAME TABLE `$original_table` TO `$new_table`";
            $DB->query($query) or die("0.78 rename $original_table to $new_table " . $LANG['update'][90] . $DB->error());
         }
      }
      if (FieldExists($new_table,'ID')) {
         // ALTER ID -> id
         $changes[$new_table][]="CHANGE `ID` `id` INT( 11 ) NOT NULL AUTO_INCREMENT";

      }
   }
   if ($backup_tables && $output) {
      echo "<div class='red'><p>You can delete backup tables if you have no need of them.</p></div>";
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : rename foreign keys'); // Updating schema

   $foreignkeys=array(
   'assign' => array(array('to' => 'users_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'assign_group' => array(array('to' => 'groups_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'assign_ent' => array(array('to' => 'suppliers_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'auth_method' => array(array('to' => 'authtype',
                           'noindex' => array('glpi_users'),
                           'tables' => array('glpi_users'),),
                     ),
   'author' => array(array('to' => 'users_id',
                           'tables' => array('glpi_ticketfollowups','glpi_knowbaseitems',
                              'glpi_tickets')),
                     ),
   'auto_update' => array(array('to' => 'autoupdatesystems_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'budget' => array(array('to' => 'budgets_id',
                           'tables' => array('glpi_infocoms')),
                     ),
   'buy_version' => array(array('to' => 'softwareversions_id_buy',
                           'tables' => array('glpi_softwarelicenses')),
                     ),
   'category' => array(array('to' => 'ticketcategories_id',
                           'tables' => array('glpi_tickets')),
                      array('to' => 'softwarecategories_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'categoryID' => array(array('to' => 'knowbaseitemcategories_id',
                           'tables' => array('glpi_knowbaseitems')),
                     ),
   'category_on_software_delete' => array(array('to' => 'softwarecategories_id_ondelete',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'category applyed when a software is deleted')),
                     ),
   'cID' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computers_softwareversions')),
                     ),
   'computer' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_tickets'),
                           'tables' => array('glpi_tickets')),
                     ),
   'computer_id' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_registrykeys')),
                     ),
   'contract_type' => array(array('to' => 'contracttypes_id',
                           'tables' => array('glpi_contracts')),
                     ),
   'default_rubdoc_tracking' => array(array('to' => 'documentcategories_id_forticket',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'default category for documents added with a ticket')),
                     ),
   'default_state' => array(array('to' => 'states_id_default',
                           'noindex' => array('glpi_ocsservers'),
                           'tables' => array('glpi_ocsservers')),
                     ),
   'device_type' => array( array('to' => 'itemtype',
                           'noindex' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_bookmarks_users','glpi_documents_items',
                                 'glpi_infocoms','glpi_links_itemtypes','glpi_networkports',
                                 'glpi_reservationitems','glpi_tickets',),
                           'tables' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms','glpi_bookmarks',
                                 'glpi_bookmarks_users','glpi_links_itemtypes',
                                 'glpi_networkports','glpi_reservationitems','glpi_tickets')),
                     ),
   'domain' => array(array('to' => 'domains_id',
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'end1' => array(array('to' => 'items_id',
                        'noindex' => array('glpi_computers_items'),
                        'tables' => array('glpi_computers_items'),
                        'comments' => array('glpi_computers_items'=>'RELATION to various table, according to itemtype (ID)')),
                  array('to' => 'networkports_id_1',
                        'noindex' => array('glpi_networkports_networkports'),
                        'tables' => array('glpi_networkports_networkports')),
                     ),
   'end2' => array(array('to' => 'computers_id',
                        'tables' => array('glpi_computers_items')),
                  array('to' => 'networkports_id_2',
                        'tables' => array('glpi_networkports_networkports')),
                     ),
   'extra_ldap_server' => array(array('to' => 'authldaps_id_extra',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'extra server')),
                     ),
   'firmware' => array(array('to' => 'networkequipmentfirmwares_id',
                           'tables' => array('glpi_networkequipments')),
                     ),
   'FK_bookmark' => array(array('to' => 'bookmarks_id',
                           'tables' => array('glpi_bookmarks_users')),
                     ),
   'FK_computers' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computerdisks',
                                       'glpi_softwarelicenses',)),
                     ),
   'FK_contact' => array(array('to' => 'contacts_id',
                           'tables' => array('glpi_contacts_suppliers')),
                     ),
   'FK_contract' => array(array('to' => 'contracts_id',
                           'noindex' => array('glpi_contracts_items'),
                           'tables' => array('glpi_contracts_suppliers','glpi_contracts_items')),
                     ),
   'FK_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms'),
                           'tables' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms')),
                     ),
   'FK_doc' => array(array('to' => 'documents_id',
                           'noindex' => array('glpi_documents_items'),
                           'tables' => array('glpi_documents_items')),
                     ),
   'FK_enterprise' => array(array('to' => 'suppliers_id',
                           'noindex' => array('glpi_contacts_suppliers','glpi_contracts_suppliers'),
                           'tables' => array('glpi_contacts_suppliers','glpi_contracts_suppliers',
                                    'glpi_infocoms')),
                     ),
   'FK_entities' => array(array('to' => 'entities_id',
                           'noindex' => array('glpi_locations','glpi_netpoints',
                              'glpi_entitydatas',),
                           'tables' => array('glpi_bookmarks','glpi_cartridgeitems',
                              'glpi_computers','glpi_consumableitems','glpi_contacts',
                              'glpi_contracts','glpi_documents','glpi_locations',
                              'glpi_netpoints','glpi_suppliers','glpi_entitydatas',
                              'glpi_groups','glpi_knowbaseitems','glpi_links',
                              'glpi_mailcollectors','glpi_monitors','glpi_networkequipments',
                              'glpi_peripherals','glpi_phones','glpi_printers',
                              'glpi_reminders','glpi_rules','glpi_softwares',
                              'glpi_softwarelicenses','glpi_tickets','glpi_users',
                              'glpi_profiles_users',),
                           'default'=> array('glpi_bookmarks' => "-1")),
                     ),
   'FK_filesystems' => array(array('to' => 'filesystems_id',
                           'tables' => array('glpi_computerdisks',)),
                     ),
   'FK_glpi_cartridges_type' => array(array('to' => 'cartridgeitems_id',
                           'tables' => array('glpi_cartridges',
                              'glpi_cartridges_printermodels')),
                     ),
   'FK_glpi_consumables_type' => array(array('to' => 'consumableitems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_consumables',)),
                     ),
   'FK_glpi_dropdown_model_printers' => array(array('to' => 'printermodels_id',
                           'noindex' => array('glpi_cartridges_printermodels'),
                           'tables' => array('glpi_cartridges_printermodels',)),
                     ),
   'FK_glpi_enterprise' => array(array('to' => 'manufacturers_id',
                     'tables' => array('glpi_cartridgeitems','glpi_computers',
                        'glpi_consumableitems','glpi_devicecases','glpi_devicecontrols',
                        'glpi_devicedrives','glpi_devicegraphiccards','glpi_deviceharddrives',
                        'glpi_devicenetworkcards','glpi_devicemotherboards','glpi_devicepcis',
                        'glpi_devicepowersupplies','glpi_deviceprocessors','glpi_devicememories',
                        'glpi_devicesoundcards','glpi_monitors','glpi_networkequipments',
                        'glpi_peripherals','glpi_phones','glpi_printers',
                        'glpi_softwares',)),
                     ),
   'FK_glpi_printers' => array(array('to' => 'printers_id',
                           'tables' => array('glpi_cartridges',)),
                     ),
   'FK_group' => array(array('to' => 'groups_id',
                           'tables' => array('glpi_tickets')),
                     ),
   'FK_groups' => array(array('to' => 'groups_id',
                           'tables' => array('glpi_computers','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares','glpi_groups_users')),
                     ),
   'FK_interface' => array(array('to' => 'interfacetypes_id',
                           'tables' => array('glpi_devicegraphiccards')),
                     ),
   'FK_item' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_mailingsettings'),
                           'tables' => array('glpi_mailingsettings')),
                     ),
   'FK_links' => array(array('to' => 'links_id',
                           'tables' => array('glpi_links_itemtypes')),
                     ),
   'FK_port' => array(array('to' => 'networkports_id',
                           'noindex' => array('glpi_networkports_vlans'),
                           'tables' => array('glpi_networkports_vlans')),
                     ),
   'FK_profiles' => array(array('to' => 'profiles_id',
                           'tables' => array('glpi_profiles_users','glpi_users')),
                     ),
   'FK_rules' => array(array('to' => 'rules_id',
                           'tables' => array('glpi_rulecriterias','glpi_ruleactions')),
                     ),
   'FK_tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_documents')),
                     ),
   'FK_users' => array(array('to' => 'users_id',
                              'noindex' => array('glpi_displaypreferences','glpi_bookmarks_users',
                                 'glpi_groups_users',),
                              'tables' => array('glpi_bookmarks', 'glpi_displaypreferences',
                                 'glpi_documents', 'glpi_groups','glpi_reminders',
                                 'glpi_bookmarks_users','glpi_groups_users','glpi_profiles_users',
                                 'glpi_computers', 'glpi_monitors',
                                 'glpi_networkequipments', 'glpi_peripherals', 'glpi_phones',
                                 'glpi_printers','glpi_softwares')),
                     ),
   'FK_vlan' => array(array('to' => 'vlans_id',
                           'tables' => array('glpi_networkports_vlans')),
                     ),
   'glpi_id' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_ocslinks')),
                     ),
   'id_assign' => array(array('to' => 'users_id',
                           'tables' => array('glpi_ticketplannings')),
                     ),
   'id_auth' => array(array('to' => 'auths_id',
                           'noindex' => array('glpi_users'),
                           'tables' => array('glpi_users'),),
                     ),
   'id_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_reservationitems'),
                           'tables' => array('glpi_reservationitems')),
                     ),
   'id_followup' => array(array('to' => 'ticketfollowups_id',
                           'tables' => array('glpi_ticketplannings')),
                     ),
   'id_item' => array(array('to' => 'reservationitems_id',
                           'tables' => array('glpi_reservations')),
                     ),
   'id_user' => array(array('to' => 'users_id',
                           'tables' => array('glpi_consumables','glpi_reservations')),
                     ),
   'iface' => array(array('to' => 'networkinterfaces_id',
                           'tables' => array('glpi_networkports')),
                     ),
   'interface' => array(array('to' => 'interfacetypes_id',
                           'tables' => array('glpi_devicecontrols','glpi_deviceharddrives',
                                 'glpi_devicedrives')),
                     ),
   'item' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_events'),
                           'tables' => array('glpi_events')),
                     ),
   'link_if_status' => array(array('to' => 'states_id_linkif',
                           'noindex' => array('glpi_ocsservers'),
                           'tables' => array('glpi_ocsservers')),
                     ),
   'location' => array(array('to' => 'locations_id',
                           'noindex' => array('glpi_netpoints'),
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_netpoints','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_users','glpi_softwares')),
                     ),
   'model' => array(array('to' => 'computermodels_id',
                           'tables' => array('glpi_computers')),
                     array('to' => 'monitormodels_id',
                           'tables' => array('glpi_monitors')),
                     array('to' => 'networkequipmentmodels_id',
                           'tables' => array('glpi_networkequipments')),
                     array('to' => 'peripheralmodels_id',
                           'tables' => array('glpi_peripherals')),
                     array('to' => 'phonemodels_id',
                           'tables' => array('glpi_phones')),
                     array('to' => 'printermodels_id',
                           'tables' => array('glpi_printers')),
                     ),
   'netpoint' => array(array('to' => 'netpoints_id',
                           'tables' => array('glpi_networkports')),
                     ),
   'network' => array(array('to' => 'networks_id',
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'ocs_id' => array(array('to' => 'ocsid',
                           'noindex' => array('glpi_ocslinks'),
                           'tables' => array('glpi_ocslinks')),
                     ),
   'ocs_server_id' => array(array('to' => 'ocsservers_id',
                           'noindex' => array('glpi_ocslinks'),
                           'tables' => array('glpi_ocsadmininfoslinks','glpi_ocslinks')),
                     ),
   'on_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_networkports'),
                           'tables' => array('glpi_networkports')),
                     ),
   'os' => array(array('to' => 'operatingsystems_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'os_sp' => array(array('to' => 'operatingsystemservicepacks_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'os_version' => array(array('to' => 'operatingsystemversions_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'parentID' => array(array('to' => 'knowbaseitemcategories_id',
                           'noindex' => array('glpi_knowbaseitemcategories'),
                           'tables' => array('glpi_knowbaseitemcategories')),
                        array('to' => 'locations_id',
                           'tables' => array('glpi_locations')),
                        array('to' => 'ticketcategories_id',
                           'tables' => array('glpi_ticketcategories')),
                        array('to' => 'entities_id',
                           'tables' => array('glpi_entities')),
                     ),
   'platform' => array(array('to' => 'operatingsystems_id',
                           'tables' => array('glpi_softwares',)),
                     ),
   'power' => array(array('to' => 'phonepowersupplies_id',
                           'tables' => array('glpi_phones')),
                     ),
   'recipient' => array(array('to' => 'users_id_recipient',
                           'tables' => array('glpi_tickets')),
                     ),
   'rubrique' => array(array('to' => 'documentcategories_id',
                           'tables' => array('glpi_documents')),
                     ),
   'rule_id' => array(array('to' => 'rules_id',
                           'tables' => array('glpi_rulecachemanufacturers',
                              'glpi_rulecachecomputermodels','glpi_rulecachemonitormodels',
                              'glpi_rulecachenetworkequipmentmodels','glpi_rulecacheperipheralmodels',
                              'glpi_rulecachephonemodels','glpi_rulecacheprintermodels',
                              'glpi_rulecacheoperatingsystems','glpi_rulecacheoperatingsystemservicepacks',
                              'glpi_rulecacheoperatingsystemversions','glpi_rulecachesoftwares',
                              'glpi_rulecachecomputertypes','glpi_rulecachemonitortypes',
                              'glpi_rulecachenetworkequipmenttypes','glpi_rulecacheperipheraltypes',
                              'glpi_rulecachephonetypes','glpi_rulecacheprintertypes',)),
                     ),
   'server_id' => array(array('to' => 'authldaps_id',
                           'tables' => array('glpi_authldapreplicates')),
                     ),
   'sID' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwarelicenses','glpi_softwareversions')),
                     ),
   'state' => array(array('to' => 'states_id',
                           'tables' => array('glpi_computers','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwareversions')),
                     ),
   'tech_num' => array(array('to' => 'users_id_tech',
                              'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares')),
                     ),
   'title' => array(array('to' => 'usertitles_id',
                           'tables' => array('glpi_users')),
                     ),
   'tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_ticketfollowups')),
                     ),
   'type' => array(array('to' => 'cartridgeitemtypes_id',
                           'tables' => array('glpi_cartridgeitems')),
                  array('to' => 'computertypes_id',
                           'tables' => array('glpi_computers')),
                  array('to' => 'consumableitemtypes_id',
                           'tables' => array('glpi_consumableitems')),
                  array('to' => 'contacttypes_id',
                           'tables' => array('glpi_contacts')),
                  array('to' => 'devicecasetypes_id',
                           'tables' => array('glpi_devicecases')),
                  array('to' => 'devicememorytypes_id',
                           'tables' => array('glpi_devicememories')),
                  array('to' => 'suppliertypes_id',
                           'tables' => array('glpi_suppliers')),
                  array('to' => 'monitortypes_id',
                           'tables' => array('glpi_monitors')),
                  array('to' => 'networkequipmenttypes_id',
                           'tables' => array('glpi_networkequipments')),
                  array('to' => 'peripheraltypes_id',
                           'tables' => array('glpi_peripherals')),
                  array('to' => 'phonetypes_id',
                           'tables' => array('glpi_phones')),
                  array('to' => 'printertypes_id',
                           'tables' => array('glpi_printers')),
                  array('to' => 'softwarelicensetypes_id',
                           'tables' => array('glpi_softwarelicenses')),
                  array('to' => 'usercategories_id',
                           'tables' => array('glpi_users')),
                  array('to' => 'itemtype', 'noindex' => array('glpi_computers_items'),
                           'tables' => array('glpi_computers_items','glpi_displaypreferences')),
                     ),
   'update_software' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'use_version' => array(array('to' => 'softwareversions_id_use',
                           'tables' => array('glpi_softwarelicenses')),
                     ),
   'vID' => array(array('to' => 'softwareversions_id',
                           'tables' => array('glpi_computers_softwareversions')),
                     ),
   );


   foreach ($foreignkeys as $oldname => $newnames) {
      foreach ($newnames as $tab) {
         $newname=$tab['to'];
         foreach ($tab['tables'] as $table) {
            $doindex=true;
            if (isset($tab['noindex'])&&in_array($table,$tab['noindex'])) {
               $doindex=false;
            }
            // Rename field
            if (FieldExists($table, $oldname)) {
               $addcomment='';
               if (isset($tab['comments']) && isset($tab['comments'][$table])) {
                  $addcomment=" COMMENT '".$tab['comments'][$table]."' ";
               }
               $default_value=0;
               if (isset($tab['default']) && isset($tab['default'][$table])) {
                  $default_value=$tab['default'][$table];
               }
               // Manage NULL fields
               $query="UPDATE `$table` SET `$oldname`='$default_value' WHERE `$oldname` IS NULL ";
               $DB->query($query) or die("0.78 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

               $changes[$table][]="CHANGE COLUMN `$oldname` `$newname` INT( 11 ) NOT NULL DEFAULT '$default_value' $addcomment";
            } else {
               $updateresult = false;
               if ($output) {
                  echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
               }
            }
            // If do index : delete old one / create new one
            if ($doindex) {
               if (!isIndex($table, $newname)) {
                  $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
               }
               if ($oldname!=$newname && isIndex($table, $oldname)) {
                  $changes[$table][]="DROP INDEX `$oldname`";
               }
            }
         }
      }
   }


   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : rename bool values'); // Updating schema

   $boolfields=array(
   'glpi_authldaps' => array(array('from' => 'ldap_use_tls', 'to' => 'use_tls', 'default' =>0, 'noindex'=>true),//
                           array('from' => 'use_dn', 'to' => 'use_dn', 'default' =>1, 'noindex'=>true),//
                     ),
   'glpi_bookmarks' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>1 ),//
                           array('from' => 'recursive','to' => 'is_recursive', 'default' =>0 ),//
                     ),
   'glpi_cartridgeitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_computers' => array(array('from' => 'is_template', 'to' => 'is_template', 'default' =>0),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'ocs_import', 'to' => 'is_ocs_import', 'default' =>0 ),//
                     ),
   'glpi_configs' => array(array('from' => 'jobs_at_login', 'to' => 'show_jobs_at_login', 'default' =>0, 'noindex'=>true),//
                           array('from' => 'mailing', 'to' => 'use_mailing', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'permit_helpdesk', 'to' => 'use_anonymous_helpdesk', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'existing_auth_server_field_clean_domain', 'to' => 'existing_auth_server_field_clean_domain', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'auto_assign', 'to' => 'use_auto_assign_to_tech', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'public_faq', 'to' => 'use_public_faq', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'url_in_mail', 'to' => 'show_link_in_mail', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'use_ajax', 'to' => 'use_ajax', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ajax_autocompletion', 'to' => 'use_ajax_autocompletion', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'auto_add_users', 'to' => 'is_users_auto_add', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'view_ID', 'to' => 'is_ids_visible', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ocs_mode', 'to' => 'use_ocs_mode', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'followup_on_update_ticket', 'to' => 'add_followup_on_update_ticket', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'licenses_alert', 'to' => 'use_licenses_alert', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'keep_tracking_on_delete', 'to' => 'keep_tickets_on_delete', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'use_errorlog', 'to' => 'use_log_in_files', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_contact', 'to' => 'is_contact_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_user', 'to' => 'is_user_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_group', 'to' => 'is_group_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_location', 'to' => 'is_location_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_contact', 'to' => 'is_contact_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_user', 'to' => 'is_user_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_group', 'to' => 'is_group_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_location', 'to' => 'is_location_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'flat_dropdowntree', 'to' => 'use_flat_dropdowntree', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoname_entity', 'to' => 'use_autoname_by_entity', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'expand_soft_categorized', 'to' => 'is_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'expand_soft_not_categorized', 'to' => 'is_not_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'ticket_title_mandatory', 'to' => 'is_ticket_title_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ticket_content_mandatory', 'to' => 'is_ticket_content_mandatory', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'ticket_category_mandatory', 'to' => 'is_ticket_category_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'followup_private', 'to' => 'followup_private', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'software_helpdesk_visible', 'to' => 'default_software_helpdesk_visible', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_consumableitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contacts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contracts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'monday', 'to' => 'use_monday', 'default' =>0 ),//
                           array('from' => 'saturday', 'to' => 'use_saturday', 'default' =>0 ),//
                     ),
   'glpi_devicecontrols' => array(array('from' => 'raid','to' => 'is_raid', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_devicedrives' => array(array('from' => 'is_writer','to' => 'is_writer', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_devicepowersupplies' => array(array('from' => 'atx','to' => 'is_atx', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_documents' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_documenttypes' => array(array('from' => 'upload','to' => 'is_uploadable', 'default' =>1, ),//
                     ),
   'glpi_groups' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_knowbaseitems' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'faq', 'to' => 'is_faq', 'default' =>0 ),//
                     ),
   'glpi_links' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_monitors' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                        array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_micro', 'to' => 'have_micro', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_speaker', 'to' => 'have_speaker', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_subd', 'to' => 'have_subd', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_bnc', 'to' => 'have_bnc', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_dvi', 'to' => 'have_dvi', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_pivot', 'to' => 'have_pivot', 'default' =>0,'noindex'=>true  ),//

                     ),
   'glpi_networkequipments' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_ocslinks' => array(array('from' => 'auto_update','to' => 'use_auto_update', 'default' =>1),//
                     ),
   'glpi_ocsservers' => array(array('from' => 'import_periph','to' => 'import_periph', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_monitor','to' => 'import_monitor', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_software','to' => 'import_software', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_printer','to' => 'import_printer', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_name','to' => 'import_general_name', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_os','to' => 'import_general_os', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_serial','to' => 'import_general_serial', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_model','to' => 'import_general_model', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_enterprise','to' => 'import_general_manufacturer', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_type','to' => 'import_general_type', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_domain','to' => 'import_general_domain', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_contact','to' => 'import_general_contact', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_comments','to' => 'import_general_comment', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_processor','to' => 'import_device_processor', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_memory','to' => 'import_device_memory', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_hdd','to' => 'import_device_hdd', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_iface','to' => 'import_device_iface', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_gfxcard','to' => 'import_device_gfxcard', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_sound','to' => 'import_device_sound', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_drives','to' => 'import_device_drive', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_ports','to' => 'import_device_port', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_modems','to' => 'import_device_modem', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_registry','to' => 'import_registry', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_os_serial','to' => 'import_os_serial', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_ip','to' => 'import_ip', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_disk','to' => 'import_disk', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_monitor_comments','to' => 'import_monitor_comment', 'default' =>0,'noindex'=>true),//
                        array('from' => 'glpi_link_enabled','to' => 'is_glpi_link_enabled', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_ip','to' => 'use_ip_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_name','to' => 'use_name_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_mac_address','to' => 'use_mac_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_serial','to' => 'use_serial_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'use_soft_dict','to' => 'use_soft_dict', 'default' =>0,'noindex'=>true),//
                     ),
   'glpi_peripherals' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_phones' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_hp', 'to' => 'have_hp', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_casque', 'to' => 'have_headset', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_printers' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_usb', 'to' => 'have_usb', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_par', 'to' => 'have_parallel', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_serial', 'to' => 'have_serial', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_profiles_users' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1),//
                           array('from' => 'dynamic','to' => 'is_dynamic', 'default' =>0),//
                     ),
   'glpi_profiles' => array(array('from' => 'is_default','to' => 'is_default', 'default' =>0),//
                     ),
   'glpi_reminders' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>1 ),//
                           array('from' => 'recursive','to' => 'is_recursive', 'default' =>0 ),//
                           array('from' => 'rv','to' => 'is_planned', 'default' =>0 ),//
                     ),
   'glpi_reservationitems' => array(array('from' => 'active','to' => 'is_active', 'default' =>1),//
                     ),
   'glpi_rules' => array(array('from' => 'active','to' => 'is_active', 'default' =>1),//
                     ),
   'glpi_suppliers' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_softwares' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'helpdesk_visible', 'to' => 'is_helpdesk_visible', 'default' =>1 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_update', 'to' => 'is_update', 'default' =>0,'noindex'=>true ),//
                     ),
   'glpi_softwarelicenses' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_tickets' => array(array('from' => 'emailupdates', 'to' => 'use_email_notification', 'default' =>0, 'noindex'=>true  ),//
                     ),
   'glpi_ticketfollowups' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>0 ),//
                     ),
   'glpi_users' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                        array('from' => 'active','to' => 'is_active', 'default' =>1),//
                        array('from' => 'jobs_at_login', 'to' => 'show_jobs_at_login', 'default' =>NULL,'maybenull'=>true, 'noindex'=>true),//
                        array('from' => 'followup_private', 'to' => 'followup_private', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'expand_soft_categorized', 'to' => 'is_categorized_soft_expanded', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'expand_soft_not_categorized', 'to' => 'is_not_categorized_soft_expanded', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'flat_dropdowntree', 'to' => 'use_flat_dropdowntree', 'default' =>NULL, 'maybenull'=>true,'noindex'=>true ),//
                        array('from' => 'view_ID', 'to' => 'is_ids_visible', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                     ),

   );

   foreach ($boolfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         // Rename field
         if (FieldExists($table, $oldname)) {
            $NULL="NOT NULL";
            if (isset($update['maybenull']) && $update['maybenull']) {
               $NULL="NULL";
            }

            $default="DEFAULT NULL";
            if (isset($update['default']) && !is_null($update['default'])) {
               $default="DEFAULT ".$update['default'];
            }

            // Manage NULL fields
            $query="UPDATE `$table` SET `$oldname`=0 WHERE `$oldname` IS NULL ;";
            $DB->query($query) or die("0.78 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            // Manage not zero values
            $query="UPDATE `$table` SET `$oldname`=1 WHERE `$oldname` <> 0; ";
            $DB->query($query) or die("0.78 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            $changes[$table][]="CHANGE `$oldname` `$newname` TINYINT( 1 ) $NULL $default";

         } else {
            $updateresult = false;
            if ($output) {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";

            }
         }
      }
   }
   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : update text fields'); // Updating schema

   $textfields=array(
   'comments' => array('to' => 'comment',
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                                 'glpi_consumableitems','glpi_contacts','glpi_contracts',
                                 'glpi_documents','glpi_autoupdatesystems','glpi_budgets',
                                 'glpi_cartridgeitemtypes','glpi_devicecasetypes','glpi_consumableitemtypes',
                                 'glpi_contacttypes','glpi_contracttypes','glpi_domains',
                                 'glpi_suppliertypes','glpi_filesystems','glpi_networkequipmentfirmwares',
                                 'glpi_networkinterfaces','glpi_interfacetypes',
                                 'glpi_knowbaseitemcategories','glpi_softwarelicensetypes','glpi_locations',
                                 'glpi_manufacturers','glpi_computermodels','glpi_monitormodels',
                                 'glpi_networkequipmentmodels','glpi_peripheralmodels','glpi_phonemodels',
                                 'glpi_printermodels','glpi_netpoints','glpi_networks',
                                 'glpi_operatingsystems','glpi_operatingsystemservicepacks','glpi_operatingsystemversions',
                                 'glpi_phonepowersupplies','glpi_devicememorytypes','glpi_documentcategories',
                                 'glpi_softwarecategories','glpi_states','glpi_ticketcategories',
                                 'glpi_usertitles','glpi_usercategories','glpi_vlans',
                                 'glpi_suppliers','glpi_entities','glpi_groups',
                                 'glpi_infocoms','glpi_monitors','glpi_phones',
                                 'glpi_printers','glpi_peripherals','glpi_networkequipments',
                                 'glpi_reservationitems','glpi_rules','glpi_softwares',
                                 'glpi_softwarelicenses','glpi_softwareversions','glpi_computertypes',
                                 'glpi_monitortypes','glpi_networkequipmenttypes','glpi_peripheraltypes',
                                 'glpi_phonetypes','glpi_printertypes','glpi_users',),
                     ),
      'notes' =>  array('to' => 'notepad', 'long'=>true,
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_contacts','glpi_contracts',
                              'glpi_documents','glpi_suppliers','glpi_entitydatas',
                              'glpi_printers','glpi_monitors','glpi_phones','glpi_peripherals',
                              'glpi_networkequipments','glpi_softwares')),

      'ldap_condition' =>  array('to' => 'condition',
                           'tables' => array('glpi_authldaps')),
      'import_printers' =>  array('to' => 'import_printer','long'=>true,
                           'tables' => array('glpi_ocslinks')),
      'contents' =>  array('to' => 'content','long'=>true,
                           'tables' => array('glpi_tickets','glpi_ticketfollowups')),
);
   foreach ($textfields as $oldname => $tab) {
      $newname=$tab['to'];
      $type="TEXT";
      if (isset($tab['long']) && $tab['long']) {
         $type="LONGTEXT";
      }
      foreach ($tab['tables'] as $table) {
         // Rename field
         if (FieldExists($table, $oldname)) {

            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` $type NULL DEFAULT NULL  ";
            $DB->query($query) or die("0.78 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            $updateresult = false;
            if ($output) {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
         }
      }
   }

   $varcharfields=array(
      'glpi_authldaps' => array(array('from' => 'ldap_host', 'to' => 'host', 'noindex'=>true),//
                        array('from' => 'ldap_basedn', 'to' => 'basedn', 'noindex'=>true),//
                        array('from' => 'ldap_rootdn', 'to' => 'rootdn', 'noindex'=>true),//
                        array('from' => 'ldap_pass', 'to' => 'rootdn_password', 'noindex'=>true),//
                        array('from' => 'ldap_login', 'to' => 'login_field', 'default'=>'uid','noindex'=>true,''),//
                        array('from' => 'ldap_field_group', 'to' => 'group_field', 'noindex'=>true),//
                        array('from' => 'ldap_group_condition', 'to' => 'group_condition', 'noindex'=>true),//
                        array('from' => 'ldap_field_group_member', 'to' => 'group_member_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_email', 'to' => 'email_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_realname', 'to' => 'realname_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_firstname', 'to' => 'firstname_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_phone', 'to' => 'phone_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_phone2', 'to' => 'phone2_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_mobile', 'to' => 'mobile_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_comments', 'to' => 'comment_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_title', 'to' => 'title_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_type', 'to' => 'category_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_language', 'to' => 'language_field', 'noindex'=>true),//
                     ),
      'glpi_authldapreplicates' => array(array('from' => 'ldap_host', 'to' => 'host', 'noindex'=>true),//
                     ),
      'glpi_authmails' => array(array('from' => 'imap_auth_server', 'to' => 'connect_string', 'noindex'=>true),//
                        array('from' => 'imap_host', 'to' => 'host', 'noindex'=>true),//
                     ),
      'glpi_computers' => array(array('from' => 'os_license_id', 'to' => 'os_licenseid', 'noindex'=>true),//
                        array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_configs' => array(array('from' => 'helpdeskhelp_url', 'to' => 'helpdesk_doc_url', 'noindex'=>true),//
                        array('from' => 'centralhelp_url', 'to' => 'central_doc_url', 'noindex'=>true),//
                     ),
      'glpi_contracts' => array(array('from' => 'compta_num', 'to' => 'accounting_number', 'noindex'=>true),//
                     ),
      'glpi_events' => array(array('from' => 'itemtype', 'to' => 'type', 'noindex'=>true),//
                     ),
      'glpi_infocoms' => array(array('from' => 'num_commande', 'to' => 'order_number', 'noindex'=>true),//
                        array('from' => 'bon_livraison', 'to' => 'delivery_number', 'noindex'=>true),//
                        array('from' => 'num_immo', 'to' => 'immo_number', 'noindex'=>true),//
                        array('from' => 'facture', 'to' => 'bill', 'noindex'=>true),//
                     ),
      'glpi_monitors' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_networkequipments' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
               array('from' => 'ifmac', 'to' => 'mac', 'noindex'=>true),//
               array('from' => 'ifaddr', 'to' => 'ip', 'noindex'=>true),//
                     ),
      'glpi_networkports' => array(array('from' => 'ifmac', 'to' => 'mac', 'noindex'=>true),//
               array('from' => 'ifaddr', 'to' => 'ip', 'noindex'=>true),//
                     ),
      'glpi_peripherals' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_phones' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_printers' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     array('from' => 'ramSize', 'to' => 'memory_size', 'noindex'=>true),//
                     ),
      'glpi_registrykeys' => array(array('from' => 'registry_hive', 'to' => 'hive', 'noindex'=>true),//
                     array('from' => 'registry_path', 'to' => 'path', 'noindex'=>true),//
                     array('from' => 'registry_value', 'to' => 'value', 'noindex'=>true),//
                     array('from' => 'registry_ocs_name', 'to' => 'ocs_name', 'noindex'=>true),//
                     ),
      'glpi_softwares' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_tickets' => array(array('from' => 'uemail', 'to' => 'user_email', 'noindex'=>true),//
                     ),
                  );
   foreach ($varcharfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT '".$update['default']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` VARCHAR( 255 ) NULL $default  ";
            $DB->query($query) or die("0.78 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            $updateresult = false;
            if ($output) {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
            $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }

   $charfields=array(
      'glpi_profiles' => array(array('from' => 'user_auth_method', 'to' => 'user_authtype', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_tracking', 'to' => 'rule_ticket', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_softwarecategories', 'to' => 'rule_softwarecategories', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_dictionnary_software', 'to' => 'rule_dictionnary_software', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_dictionnary_dropdown', 'to' => 'rule_dictionnary_dropdown', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                        ),
      'glpi_configs' => array(array('from' => 'version', 'to' => 'version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'version', 'to' => 'version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'language', 'to' => 'language', 'length'=>10,'default' =>'en_GB', 'noindex'=>true, 'comments'=>'see define.php CFG_GLPI[language] array'),//
               array('from' => 'priority_1', 'to' => 'priority_1', 'length'=>20,'default' =>'#fff2f2', 'noindex'=>true),//
               array('from' => 'priority_2', 'to' => 'priority_2', 'length'=>20,'default' =>'#ffe0e0', 'noindex'=>true),//
               array('from' => 'priority_3', 'to' => 'priority_3', 'length'=>20,'default' =>'#ffcece', 'noindex'=>true),//
               array('from' => 'priority_4', 'to' => 'priority_4', 'length'=>20,'default' =>'#ffbfbf', 'noindex'=>true),//
               array('from' => 'priority_5', 'to' => 'priority_5', 'length'=>20,'default' =>'#ffadad', 'noindex'=>true),//
               array('from' => 'founded_new_version', 'to' => 'founded_new_version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
                        ),
      'glpi_rules' => array(array('from' => 'match', 'to' => 'match', 'length'=>10,'default' =>NULL, 'noindex'=>true,'comments'=>'see define.php *_MATCHING constant'),//
                        ),
      'glpi_users' => array(array('from' => 'language', 'to' => 'language', 'length'=>10,'default' =>NULL, 'noindex'=>true, 'comments'=>'see define.php CFG_GLPI[language] array'),//
               array('from' => 'priority_1', 'to' => 'priority_1', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_2', 'to' => 'priority_2', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_3', 'to' => 'priority_3', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_4', 'to' => 'priority_4', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_5', 'to' => 'priority_5', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
                        ),

                     );
   foreach ($charfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $length=$update['length'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT '".$update['default']."'";
         }
         $addcomment="";
         if (isset($update['comments']) ) {
            $addcomment="COMMENT '".$update['comments']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` CHAR( $length ) NULL $default $addcomment ";
            $DB->query($query) or die("0.78 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            $updateresult = false;
            if ($output) {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($oldname!=$newname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }
   $intfields=array(
      'glpi_authldaps' => array(array('from' => 'ldap_port', 'to' => 'port', 'default' =>389, 'noindex'=>true,'checkdatas'=>true),//
                     array('from' => 'ldap_search_for_groups', 'to' => 'group_search_type', 'default' =>0, 'noindex'=>true),//
                     array('from' => 'ldap_opt_deref', 'to' => 'deref_option', 'default' =>0, 'noindex'=>true),//
                     array('from' => 'timezone', 'to' => 'time_offset', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              ),
      'glpi_authldapreplicates' => array(array('from' => 'ldap_port', 'to' => 'port', 'default' =>389, 'noindex'=>true,'checkdatas'=>true),//
                     ),
      'glpi_bookmarks' => array(array('from' => 'type', 'to' => 'type', 'default' =>0, 'noindex'=>true,'comments'=>'see define.php BOOKMARK_* constant'),//
                     ),
      'glpi_cartridgeitems' => array(array('from' => 'alarm', 'to' => 'alarm_threshold', 'default' =>10,),//
                              ),
      'glpi_configs' => array(array('from' => 'glpi_timezone', 'to' => 'time_offset', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'cartridges_alarm', 'to' => 'default_alarm_threshold', 'default' =>10, 'noindex'=>true),//
                              array('from' => 'event_loglevel', 'to' => 'event_loglevel', 'default' =>5, 'noindex'=>true),//
                              array('from' => 'cas_port', 'to' => 'cas_port', 'default' =>443, 'noindex'=>true,'checkdatas'=>true),//
                              array('from' => 'auto_update_check', 'to' => 'auto_update_check', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'dateformat', 'to' => 'date_format', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'numberformat', 'to' => 'number_format', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'proxy_port', 'to' => 'proxy_port', 'default' =>8080, 'noindex'=>true,'checkdatas'=>true),//
                              array('from' => 'contract_alerts', 'to' => 'default_contract_alert', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'infocom_alerts', 'to' => 'default_infocom_alert', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'cartridges_alert', 'to' => 'cartridges_alert_repeat', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'consumables_alert', 'to' => 'consumables_alert_repeat', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'monitors_management_restrict', 'to' => 'monitors_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'phones_management_restrict', 'to' => 'phones_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'peripherals_management_restrict', 'to' => 'peripherals_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'printers_management_restrict', 'to' => 'printers_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'autoupdate_link_state', 'to' => 'state_autoupdate_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'autoclean_link_state', 'to' => 'state_autoclean_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'name_display_order', 'to' => 'names_format', 'default' =>0, 'noindex'=>true,'comments'=>'see *NAME_BEFORE constant in define.php'),//
                              array('from' => 'dropdown_limit', 'to' => 'dropdown_chars_limit', 'default' =>50, 'noindex'=>true),//
                              array('from' => 'smtp_mode', 'to' => 'smtp_mode', 'default' =>0, 'noindex'=>true,'comments'=>'see define.php MAIL_* constant'),//
                              array('from' => 'mailgate_filesize_max', 'to' => 'default_mailcollector_filesize_max', 'default' =>2097152, 'noindex'=>true),//
                              ),
      'glpi_consumableitems' => array(array('from' => 'alarm', 'to' => 'alarm_threshold', 'default' =>10,),//
                              ),
      'glpi_contracts' => array(array('from' => 'duration', 'to' => 'duration', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'notice', 'to' => 'notice', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'periodicity', 'to' => 'periodicity', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'facturation', 'to' => 'billing', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'device_countmax', 'to' => 'max_links_allowed', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'alert', 'to' => 'alert', 'default' =>0),//
                              array('from' => 'renewal', 'to' => 'renewal', 'default' =>0, 'noindex'=>true),//
                              ),
      'glpi_displaypreferences' => array(array('from' => 'num', 'to' => 'num', 'default' =>0,),//
                              array('from' => 'rank', 'to' => 'rank', 'default' =>0,),//
                              ),
      'glpi_events' => array(array('from' => 'level', 'to' => 'level', 'default' =>0,),//
                              ),
      'glpi_infocoms' => array(array('from' => 'warranty_duration', 'to' => 'warranty_duration', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'amort_time', 'to' => 'sink_time', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'amort_type', 'to' => 'sink_type', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'alert', 'to' => 'alert', 'default' =>0),//
                              ),
      'glpi_mailingsettings' => array(array('from' => 'item_type', 'to' => 'mailingtype', 'default' =>0,'noindex'=>true,'comments'=>'see define.php *_MAILING_TYPE constant'),//
                     ),
      'glpi_monitors' => array(array('from' => 'size', 'to' => 'size', 'default' =>0,'noindex'=>true),//
                     ),
      'glpi_printers' => array(array('from' => 'initial_pages', 'to' => 'init_pages_counter', 'default' =>0,'noindex'=>true,'checkdatas'=>true),//
                     ),
      'glpi_profiles' => array(array('from' => 'helpdesk_hardware', 'to' => 'helpdesk_hardware', 'default' =>0,'noindex'=>true),//
                     ),
      'glpi_plugins' => array(array('from' => 'state', 'to' => 'state', 'default' =>0,'comments'=>'see define.php PLUGIN_* constant'),//
                     ),
      'glpi_reminders' => array(array('from' => 'state', 'to' => 'state', 'default' =>0,),//
                     ),
      'glpi_ticketplannings' => array(array('from' => 'state', 'to' => 'state', 'default' =>1,),//
                     ),
      'glpi_rulecriterias' => array(array('from' => 'condition', 'to' => 'condition', 'default' =>0,'comments'=>'see define.php PATTERN_* and REGEX_* constant'),//
                     ),
      'glpi_rules' => array(array('from' => 'sub_type', 'to' => 'sub_type', 'default' =>0,'comments'=>'see define.php RULE_* constant'),//
                     ),
      'glpi_tickets' => array(array('from' => 'request_type', 'to' => 'request_type', 'default' =>0,'noindex'=>true,),//
                        array('from' => 'priority', 'to' => 'priority', 'default' =>1,'noindex'=>true,),//
                     ),
      'glpi_transfers' => array(array('from' => 'keep_tickets', 'to' => 'keep_ticket', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_networklinks', 'to' => 'keep_networklink', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_reservations', 'to' => 'keep_reservation', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_history', 'to' => 'keep_history', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_devices', 'to' => 'keep_device', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_infocoms', 'to' => 'keep_infocom', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_monitor', 'to' => 'keep_dc_monitor', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_monitor', 'to' => 'clean_dc_monitor', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_phone', 'to' => 'keep_dc_phone', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_phone', 'to' => 'clean_dc_phone', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_peripheral', 'to' => 'keep_dc_peripheral', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_peripheral', 'to' => 'clean_dc_peripheral', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_printer', 'to' => 'keep_dc_printer', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_printer', 'to' => 'clean_dc_printer', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_enterprises', 'to' => 'keep_supplier', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_enterprises', 'to' => 'clean_supplier', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_contacts', 'to' => 'keep_contact', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_contacts', 'to' => 'clean_contact', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_contracts', 'to' => 'keep_contract', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_contracts', 'to' => 'clean_contract', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_softwares', 'to' => 'keep_software', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_softwares', 'to' => 'clean_software', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_documents', 'to' => 'keep_document', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_documents', 'to' => 'clean_document', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_cartridges_type', 'to' => 'keep_cartridgeitem', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_cartridges_type', 'to' => 'clean_cartridgeitem', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_cartridges', 'to' => 'keep_cartridge', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_consumables', 'to' => 'keep_consumable', 'default' =>0, 'noindex'=>true),//
                              ),
      'glpi_users' => array(array('from' => 'dateformat', 'to' => 'date_format', 'default' =>NULL, 'noindex'=>true, 'maybenull'=>true),//
                              array('from' => 'numberformat', 'to' => 'number_format', 'default' =>NULL, 'noindex'=>true, 'maybenull'=>true),//
                              array('from' => 'use_mode', 'to' => 'use_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'dropdown_limit', 'to' => 'dropdown_chars_limit', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true),//
                              ),
                     );
   foreach ($intfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }

         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT ".$update['default']."";
         }

         $NULL="NOT NULL";
         if (isset($update['maybenull']) && $update['maybenull']) {
            $NULL="NULL";
         }
         $check_datas=false;
         if (isset($update['checkdatas']) ) {
            $check_datas=$update['checkdatas'];
         }
         $addcomment="";
         if (isset($update['comments']) ) {
            $addcomment="COMMENT '".$update['comments']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            if ($check_datas) {
               $query="SELECT id, $oldname FROM $table;";
               if ($result=$DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        if (empty($data[$oldname]) && isset($update['default'])) {
                           $data[$oldname]=$update['default'];
                        }
                        $query="UPDATE $table SET $oldname='".intval($data[$oldname])."' WHERE id = ".$data['id'].";";
                        $DB->query($query);
                     }
                  }
               }
            }
            $changes[$table][]="CHANGE `$oldname` `$newname` INT( 11 ) $NULL $default $addcomment";

         } else {
            $updateresult = false;
            if ($output) {
              echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : others field changes'); // Updating schema

   if (FieldExists('glpi_alerts', 'date')) {
      $changes['glpi_alerts'][]="CHANGE `date` `date` DATETIME NOT NULL";
   }
   if (FieldExists('glpi_configs', 'date_fiscale')) {
      $changes['glpi_configs'][]="CHANGE `date_fiscale` `date_tax` DATE NOT NULL DEFAULT '2005-12-31'";
   }

   if (FieldExists('glpi_configs', 'sendexpire')) {
      $changes['glpi_configs'][]="DROP `sendexpire`";
   }
   if (FieldExists('glpi_configs', 'show_admin_doc')) {
      $changes['glpi_configs'][]="DROP `show_admin_doc`";
   }
   if (FieldExists('glpi_configs', 'licenses_management_restrict')) {
      $changes['glpi_configs'][]="DROP `licenses_management_restrict`";
   }
   if (FieldExists('glpi_configs', 'nextprev_item')) {
      $changes['glpi_configs'][]="DROP `nextprev_item`";
   }

   if (FieldExists('glpi_configs', 'logotxt')) {
      $changes['glpi_configs'][]="DROP `logotxt`";
   }

   if (FieldExists('glpi_configs', 'num_of_events')) {
      $changes['glpi_configs'][]="DROP `num_of_events`";
   }

   if (FieldExists('glpi_configs', 'tracking_order')) {
      $changes['glpi_configs'][]="DROP `tracking_order`";
   }

   if (FieldExists('glpi_contracts', 'bill_type')) {
      $changes['glpi_contracts'][]="DROP `bill_type`";
   }

   if (FieldExists('glpi_infocoms', 'amort_coeff')) {
      $changes['glpi_infocoms'][]="CHANGE `amort_coeff` `sink_coeff` FLOAT NOT NULL DEFAULT '0'";
   }

   if (FieldExists('glpi_ocsservers', 'import_software_comments')) {
      $changes['glpi_ocsservers'][]="DROP `import_software_comments`";
   }

   if (FieldExists('glpi_users', 'nextprev_item')) {
      $changes['glpi_users'][]="DROP `nextprev_item`";
   }

   if (FieldExists('glpi_users', 'num_of_events')) {
      $changes['glpi_users'][]="DROP `num_of_events`";
   }

   if (FieldExists('glpi_users', 'tracking_order')) {
      $changes['glpi_users'][]="DROP `tracking_order`";
   }

   if (FieldExists('glpi_ruleldapparameters', 'sub_type')) {
      $changes['glpi_ruleldapparameters'][]="DROP `sub_type`";
   }

   if (FieldExists('glpi_softwares', 'oldstate')) {
      $changes['glpi_softwares'][]="DROP `oldstate`";
   }

   if (FieldExists('glpi_users', 'password')) {
      $changes['glpi_users'][]="DROP `password`";
   }

   if (FieldExists('glpi_users', 'password_md5')) {
      $changes['glpi_users'][]="CHANGE `password_md5` `password` CHAR( 40 )  NULL DEFAULT NULL";
   }

   if (!FieldExists('glpi_mailcollectors', 'filesize_max')) {
      $changes['glpi_mailcollectors'][]="ADD `filesize_max` INT(11) NOT NULL DEFAULT 2097152";
   }


   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : index management'); // Updating schema

   if (!isIndex('glpi_alerts', 'unicity')) {
      $changes['glpi_alerts'][]="ADD UNIQUE `unicity` ( `itemtype` , `items_id` , `type` )";
   }

   if (!isIndex('glpi_cartridges_printermodels', 'unicity')) {
      $changes['glpi_cartridges_printermodels'][]="ADD UNIQUE `unicity` ( `printermodels_id` , `cartridgeitems_id`)";
   }

   if (!isIndex('glpi_computers_items', 'unicity')) {
      $changes['glpi_computers_items'][]="ADD UNIQUE `unicity` ( `itemtype` , `items_id`, `computers_id`)";
  }

   if (!isIndex('glpi_contacts_suppliers', 'unicity')) {
      $changes['glpi_contacts_suppliers'][]="ADD UNIQUE `unicity` ( `suppliers_id` , `contacts_id`)";
  }

   if (!isIndex('glpi_contracts_items', 'unicity')) {
      $changes['glpi_contracts_items'][]="ADD UNIQUE `unicity` ( `contracts_id` ,  `itemtype` , `items_id`)";
   }

   if (!isIndex('glpi_contracts_items', 'item')) {
      $changes['glpi_contracts_items'][]="ADD INDEX `item` ( `itemtype` , `items_id`)";
   }

   if (!isIndex('glpi_contracts_suppliers', 'unicity')) {
      $changes['glpi_contracts_suppliers'][]="ADD UNIQUE `unicity` ( `suppliers_id` , `contracts_id`)";
   }

   if (!isIndex('glpi_displaypreferences', 'unicity')) {
      $changes['glpi_displaypreferences'][]="ADD UNIQUE `unicity` ( `users_id` , `itemtype`, `num`)";
   }

   if (!isIndex('glpi_bookmarks_users', 'unicity')) {
      $changes['glpi_bookmarks_users'][]="ADD UNIQUE `unicity` ( `users_id` , `itemtype`)";
   }

   if (!isIndex('glpi_documents_items', 'unicity')) {
      $changes['glpi_documents_items'][]="ADD UNIQUE `unicity` ( `documents_id` , `itemtype`, `items_id`)";
   }

   if (!isIndex('glpi_documents_items', 'item')) {
      $changes['glpi_documents_items'][]="ADD INDEX `item` (  `itemtype`, `items_id`)";
   }

   if (!isIndex('glpi_knowbaseitemcategories', 'unicity')) {
      $changes['glpi_knowbaseitemcategories'][]="ADD UNIQUE `unicity` ( `knowbaseitemcategories_id` , `name`) ";
   }

   if (!isIndex('glpi_locations', 'unicity')) {
      $changes['glpi_locations'][]="ADD UNIQUE `unicity` ( `entities_id`, `locations_id` , `name`) ";
   }

   if (isIndex('glpi_locations', 'name')) {
      $changes['glpi_locations'][]="DROP INDEX `name` ";
   }

   if (!isIndex('glpi_netpoints', 'complete')) {
      $changes['glpi_netpoints'][]="ADD INDEX `complete` (`entities_id`,`locations_id`,`name`) ";
   }

   if (!isIndex('glpi_netpoints', 'location_name')) {
      $changes['glpi_netpoints'][]="ADD INDEX `location_name` (`locations_id`,`name`)";
   }

   if (!isIndex('glpi_entities', 'unicity')) {
      $changes['glpi_entities'][]="ADD UNIQUE `unicity` (`entities_id`,`name`)  ";
   }

   if (!isIndex('glpi_entitydatas', 'unicity')) {
      $changes['glpi_entitydatas'][]="ADD UNIQUE `unicity` (`entities_id`) ";
   }

   if (!isIndex('glpi_events', 'item')) {
      $changes['glpi_events'][]="ADD INDEX `item` (`type`,`items_id`) ";
   }

   if (!isIndex('glpi_infocoms', 'unicity')) {
      $changes['glpi_infocoms'][]="ADD UNIQUE `unicity` (`itemtype`,`items_id`)  ";
   }
   if (!isIndex('glpi_knowbaseitems', 'date_mod')) {
      $changes['glpi_knowbaseitems'][]="ADD INDEX `date_mod` (`date_mod`) ";
   }

   if (!isIndex('glpi_networkequipments', 'date_mod')) {
      $changes['glpi_networkequipments'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_links_itemtypes', 'unicity')) {
      $changes['glpi_links_itemtypes'][]="ADD UNIQUE `unicity` (`itemtype`,`links_id`)   ";
   }

   if (!isIndex('glpi_mailingsettings', 'unicity')) {
      $changes['glpi_mailingsettings'][]="ADD UNIQUE `unicity` (`type`,`items_id`,`mailingtype`)  ";
   }

   if (!isIndex('glpi_networkports', 'item')) {
      $changes['glpi_networkports'][]="ADD INDEX `item` (`itemtype`,`items_id`) ";
   }

   if (!isIndex('glpi_networkports_vlans', 'unicity')) {
      $changes['glpi_networkports_vlans'][]="ADD UNIQUE `unicity` (`networkports_id`,`vlans_id`) ";
   }

   if (!isIndex('glpi_networkports_networkports', 'unicity')) {
      $changes['glpi_networkports_networkports'][]="ADD UNIQUE `unicity` (`networkports_id_1`,`networkports_id_2`)  ";
   }

   if (!isIndex('glpi_ocslinks', 'unicity')) {
      $changes['glpi_ocslinks'][]="ADD UNIQUE `unicity` (`ocsservers_id`,`ocsid`)   ";
   }

   if (!isIndex('glpi_peripherals', 'date_mod')) {
      $changes['glpi_peripherals'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_phones', 'date_mod')) {
      $changes['glpi_phones'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_plugins', 'unicity')) {
      $changes['glpi_plugins'][]="ADD UNIQUE `unicity` (`directory`)   ";
   }

   if (!isIndex('glpi_printers', 'date_mod')) {
      $changes['glpi_printers'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_reminders', 'date_mod')) {
      $changes['glpi_reminders'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_reservationitems', 'item')) {
      $changes['glpi_reservationitems'][]="ADD INDEX `item` (`itemtype`,`items_id`)   ";
   }

   if (!isIndex('glpi_tickets', 'item')) {
      $changes['glpi_tickets'][]="ADD INDEX `item` (`itemtype`,`items_id`)  ";
   }

   if (!isIndex('glpi_documenttypes', 'date_mod')) {
      $changes['glpi_documenttypes'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_documenttypes', 'unicity')) {
      $changes['glpi_documenttypes'][]="ADD UNIQUE `unicity` (`ext`)  ";
   }
   if (!isIndex('glpi_users', 'unicity')) {
      $changes['glpi_users'][]="ADD UNIQUE `unicity` (`name`)  ";
   }
   if (!isIndex('glpi_users', 'date_mod')) {
      $changes['glpi_users'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }
   if (!isIndex('glpi_users', 'authitem')) {
      $changes['glpi_users'][]="ADD INDEX `authitem` (`authtype`,`auths_id`) ";
   }
   if (!isIndex('glpi_groups_users', 'unicity')) {
      $changes['glpi_groups_users'][]="ADD UNIQUE `unicity` (`users_id`,`groups_id`)  ";
   }

   $indextodrop=array(
         'glpi_alerts' => array('alert','FK_device'),
         'glpi_cartridges_printermodels' => array('FK_glpi_type_printer'),
         'glpi_computers_items' => array('connect','type','end1','end1_2'),
         'glpi_consumables' => array('FK_glpi_cartridges_type'),
         'glpi_contacts_suppliers' => array('FK_enterprise'),
         'glpi_contracts_items' => array('FK_contract_device','device_type'),
         'glpi_contracts_suppliers' => array('FK_enterprise'),
         'glpi_displaypreferences' => array('display','FK_users'),
         'glpi_bookmarks_users' => array('FK_users'),
         'glpi_documents_items' => array('FK_doc_device','device_type','FK_device'),
         'glpi_knowbaseitemcategories' => array('parentID_2','parentID'),
         'glpi_locations' => array('FK_entities'),
         'glpi_netpoints' => array('FK_entities','location'),
         'glpi_entities' => array('name'/*,'parentID'*/),
         'glpi_entitydatas' => array('FK_entities'),
         'glpi_events' => array('comp','itemtype'),
         'glpi_infocoms' => array('FK_device'),
         'glpi_computers_softwareversions' => array('sID'),
         'glpi_links_itemtypes' => array('link'),
         'glpi_mailingsettings' => array('mailings','FK_item'),
         'glpi_networkports' => array('device_type'),
         'glpi_networkports_vlans' => array('portvlan'),
         'glpi_networkports_networkports' => array('netwire','end1','end1_2'),
         'glpi_ocslinks' => array('ocs_server_id'),
         'glpi_plugins' => array('name'),
         'glpi_reservationitems' => array('reservationitem'),
         'glpi_tickets' => array('computer','device_type'),
         'glpi_documenttypes' => array('extension'),
         'glpi_users' => array('name'),
         'glpi_groups_users' => array('usergroup'),
      );
   foreach ($indextodrop as $table => $tab) {
      foreach ($tab as $indexname) {
         if (isIndex($table, $indexname)) {
            $changes[$table][]="DROP INDEX `$indexname`";
         }
      }
   }

   foreach ($changes as $table => $tab) {
      displayMigrationMessage("078", $LANG['update'][141] . ' - ' . $table); // Updating schema
      $query="ALTER TABLE `$table` ".implode($tab," ,\n").";";
      $DB->query($query) or die("0.78 multiple alter in $table " . $LANG['update'][90] . $DB->error());
   }


   displayMigrationMessage("078", $LANG['update'][141] . ' - Update itemtype fields'); // Updating schema

   // Convert itemtype to Class names
   $typetoname=array(
      GENERAL_TYPE => "",// For tickets
      COMPUTER_TYPE => "Computer",
      NETWORKING_TYPE => "NetworkEquipment",
      PRINTER_TYPE => "Printer",
      MONITOR_TYPE => "Monitor",
      PERIPHERAL_TYPE => "Peripheral",
      SOFTWARE_TYPE => "Software",
      CONTACT_TYPE => "Contact",
      ENTERPRISE_TYPE => "Supplier",
      INFOCOM_TYPE => "Infocom",
      CONTRACT_TYPE => "Contract",
      CARTRIDGEITEM_TYPE => "CartridgeItem",
      TYPEDOC_TYPE => "DocumentType",
      DOCUMENT_TYPE => "Document",
      KNOWBASE_TYPE => "KnowbaseItem",
      USER_TYPE => "User",
      TRACKING_TYPE => "Ticket",
      CONSUMABLEITEM_TYPE => "ConsumableItem",
      CONSUMABLE_TYPE => "Consumable",
      CARTRIDGE_TYPE => "Cartridge",
      SOFTWARELICENSE_TYPE => "SoftwareLicense",
      LINK_TYPE => "Link",
      STATE_TYPE => "States",
      PHONE_TYPE => "Phone",
      DEVICE_TYPE => "Device",
      REMINDER_TYPE => "Reminder",
      STAT_TYPE => "Stat",
      GROUP_TYPE => "Group",
      ENTITY_TYPE => "Entity",
      RESERVATION_TYPE => "ReservationItem",
      AUTHMAIL_TYPE => "AuthMail",
      AUTHLDAP_TYPE => "AuthLDAP",
      OCSNG_TYPE => "OcsServer",
      REGISTRY_TYPE => "RegistryKey",
      PROFILE_TYPE => "Profile",
      MAILGATE_TYPE => "MailCollector",
      RULE_TYPE => "Rule",
      TRANSFER_TYPE => "Transfer",
      BOOKMARK_TYPE => "Bookmark",
      SOFTWAREVERSION_TYPE => "SoftwareVersion",
      PLUGIN_TYPE => "Plugin",
      COMPUTERDISK_TYPE => "ComputerDisk",
      NETWORKING_PORT_TYPE => "NetworkPort",
      FOLLOWUP_TYPE => "TicketFollowup",
      BUDGET_TYPE => "Budget",
      // End is not used in 0.72.x
   );
   $devtypetoname = array(MOBOARD_DEVICE     => 'DeviceMotherboard',
                          PROCESSOR_DEVICE   => 'DeviceProcessor',
                          RAM_DEVICE         => 'DeviceMemory',
                          HDD_DEVICE         => 'DeviceHardDrive',
                          NETWORK_DEVICE     => 'DeviceNetworkCard',
                          DRIVE_DEVICE       => 'DeviceDrive',
                          CONTROL_DEVICE     => 'DeviceControl',
                          GFX_DEVICE         => 'DeviceGraphicCard',
                          SND_DEVICE         => 'DeviceSoundCard',
                          PCI_DEVICE         => 'DevicePci',
                          CASE_DEVICE        => 'DeviceCase',
                          POWER_DEVICE       => 'DevicePowerSupply');

   $itemtype_tables=array("glpi_alerts", "glpi_bookmarks", "glpi_bookmarks_users",
      "glpi_computers_items", "glpi_contracts_items", "glpi_displaypreferences",
      "glpi_documents_items", "glpi_infocoms", "glpi_links_itemtypes",
      "glpi_networkports", "glpi_reservationitems", "glpi_tickets",
      );

   foreach ($itemtype_tables as $table) {
      displayMigrationMessage("078", $LANG['update'][142] . " - $table"); // Updating data
      // Alter itemtype field
      $query = "ALTER TABLE `$table` CHANGE `itemtype` `itemtype` VARCHAR( 100 ) NOT NULL";
      $DB->query($query) or die("0.78 alter itemtype of table $table " . $LANG['update'][90] . $DB->error());

      // Update values
      foreach ($typetoname as $key => $val) {
         $query = "UPDATE `$table` SET `itemtype` = '$val' WHERE `itemtype` = '$key'";
         $DB->query($query) or die("0.78 update itemtype of table $table for $val " . $LANG['update'][90] . $DB->error());
      }
   }

   if (FieldExists('glpi_logs', 'device_type')) {

      // History migration, handled separatly for optimization
      displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_logs - 1'); // Updating schema
      $query = "ALTER TABLE `glpi_logs`
               CHANGE `ID` `id` INT( 11 ) NOT NULL AUTO_INCREMENT,
               ADD `itemtype` VARCHAR(100) NOT NULL DEFAULT ''  AFTER `device_type`,
               ADD `items_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER `itemtype`,
               ADD `itemtype_link` VARCHAR(100) NOT NULL DEFAULT '' AFTER `device_internal_type`,
               CHANGE `linked_action` `linked_action` INT( 11 ) NOT NULL DEFAULT '0'
                        COMMENT 'see define.php HISTORY_* constant'";
            $DB->query($query) or die("0.78 add item* fields to table glpi_logs " . $LANG['update'][90] . $DB->error());

      // Update values
      displayMigrationMessage("078", $LANG['update'][142] . ' - glpi_logs'); // Updating schema
      foreach ($typetoname as $key => $val) {
         $query = "UPDATE `glpi_logs` SET `itemtype` = '$val', `items_id`=`FK_glpi_device`
                  WHERE `device_type` = '$key'";
         $DB->query($query) or die("0.78 update itemtype of table glpi_logs for $val " . $LANG['update'][90] . $DB->error());

         $query = "UPDATE `glpi_logs` SET `itemtype_link` = '$val'
                  WHERE `device_internal_type` = '$key'
                     AND `linked_action` IN (".HISTORY_ADD_RELATION.",".HISTORY_DEL_RELATION.",".
                                                HISTORY_DISCONNECT_DEVICE.",".HISTORY_CONNECT_DEVICE.")";
         $DB->query($query) or die("0.78 update itemtype of table glpi_logs for $val " . $LANG['update'][90] . $DB->error());
      }

      foreach ($devtypetoname as $key => $val) {
         $query = "UPDATE `glpi_logs` SET `itemtype_link` = '$val'
                  WHERE `device_internal_type` = '$key'
                     AND `linked_action` IN (".HISTORY_ADD_DEVICE.",".HISTORY_UPDATE_DEVICE.",".HISTORY_DELETE_DEVICE.")";
         $DB->query($query) or die("0.78 update itemtype of table glpi_logs for $val " . $LANG['update'][90] . $DB->error());
      }

      displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_logs - 2'); // Updating schema
      $query = "ALTER TABLE `glpi_logs`
               DROP `device_type`,
               DROP `FK_glpi_device`,
               DROP `device_internal_type`,
               ADD INDEX `itemtype_link` (`itemtype_link`),
               ADD INDEX `item` (`itemtype`,`items_id`)";
      $DB->query($query) or die("0.78 drop device* fields to table glpi_logs " . $LANG['update'][90] . $DB->error());
   }

   // Update glpi_profiles item_type

   displayMigrationMessage("078", $LANG['update'][141] . ' - Clean DB : post actions after renaming'); // Updating schema

   if (!isIndex('glpi_locations', 'name')) {
      $query=" ALTER TABLE `glpi_locations` ADD INDEX `name` (`name`)";
      $DB->query($query) or die("0.78 add name index in glpi_locations " . $LANG['update'][90] . $DB->error());
   }


   // Update values of mailcollectors
   $query="SELECT default_mailcollector_filesize_max FROM glpi_configs WHERE id=1";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         $query="UPDATE glpi_mailcollectors SET filesize_max='".$DB->result($result,0,0)."';";
         $DB->query($query);
      }
   }


   // For compatiblity with updates from past versions
   regenerateTreeCompleteName("glpi_locations");
   regenerateTreeCompleteName("glpi_knowbaseitemcategories");
   regenerateTreeCompleteName("glpi_ticketcategories");

   // Update timezone values
   if (FieldExists('glpi_configs', 'time_offset')) {
      $query="UPDATE glpi_configs SET time_offset=time_offset*3600";
      $DB->query($query) or die("0.78 update time_offset value in glpi_configs " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_authldaps', 'time_offset')) {
      $query="UPDATE glpi_authldaps SET time_offset=time_offset*3600";
      $DB->query($query) or die("0.78 update time_offset value in glpi_authldaps " . $LANG['update'][90] . $DB->error());
   }


   // Change defaults store values :
   if (FieldExists('glpi_softwares', 'sofwtares_id')) {
      $query="UPDATE glpi_softwares SET sofwtares_id=0 WHERE sofwtares_id < 0";
      $DB->query($query) or die("0.78 update default value of sofwtares_id in glpi_softwares " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users', 'authtype')) {
      $query="UPDATE glpi_users SET authtype=0 WHERE authtype < 0";
      $DB->query($query) or die("0.78 update default value of authtype in glpi_users " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users', 'auths_id')) {
      $query="UPDATE glpi_users SET auths_id=0 WHERE auths_id < 0";
      $DB->query($query) or die("0.78 update default value of auths_id in glpi_users " . $LANG['update'][90] . $DB->error());
   }
   // Update glpi_ocsadmininfoslinks table for new field name
   if (FieldExists('glpi_ocsadmininfoslinks', 'glpi_column')) {
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='locations_id' WHERE glpi_column = 'location'";
      $DB->query($query) or die("0.78 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='networks_id' WHERE glpi_column = 'network'";
      $DB->query($query) or die("0.78 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='groups_id' WHERE glpi_column = 'FK_groups'";
      $DB->query($query) or die("0.78 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
   }

   // Update bookmarks for new columns fields
   if (FieldExists('glpi_bookmarks', 'query')) {
      // All search
      $olds = array("deleted",);

      $news   = array("is_deleted",);
      foreach ($olds as $key => $val) {
         $olds[$key]="&$val=";
      }
      foreach ($news as $key => $val) {
         $news[$key]="&$val=";
      }
      $query="SELECT id, query FROM glpi_bookmarks WHERE type=".BOOKMARK_SEARCH." ;";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(str_replace($olds,$news,$data['query']))."' WHERE id=".$data['id'].";";
               $DB->query($query2) or die("0.78 update all bookmarks " . $LANG['update'][90] . $DB->error());
            }
         }
      }

      // Update bookmarks due to FHS change
      $query2="UPDATE glpi_bookmarks SET path='front/documenttype.php' WHERE path='front/typedoc.php';";
      $DB->query($query2) or die("0.78 update typedoc bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/consumableitem.php' WHERE path='front/consumable.php';";
      $DB->query($query2) or die("0.78 update consumable bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/cartridgeitem.php' WHERE path='front/cartridge.php';";
      $DB->query($query2) or die("0.78 update cartridge bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/ticket.php' WHERE path='front/tracking.php';";
      $DB->query($query2) or die("0.78 update ticket bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/mailcollector.php' WHERE path='front/mailgate.php';";
      $DB->query($query2) or die("0.78 update mailcollector bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/ocsserver.php' WHERE path='front/setup.ocsng.php';";
      $DB->query($query2) or die("0.78 update ocsserver bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/supplier.php' WHERE path='front/enterprise.php';";
      $DB->query($query2) or die("0.78 update supplier bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/networkequipment.php' WHERE path='front/networking.php';";
      $DB->query($query2) or die("0.78 update networkequipment bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/states.php' WHERE path='front/state.php';";
      $DB->query($query2) or die("0.78 update states bookmarks " . $LANG['update'][90] . $DB->error());

   }

   //// Upgrade rules datas
   // For Rule::RULE_AFFECT_RIGHTS
   $changes[1]=array('FK_entities'=>'entities_id', 'FK_profiles'=>'profiles_id',
                        'recursive'=>'is_recursive','active'=>'is_active');
   // For Rule::RULE_DICTIONNARY_SOFTWARE
   $changes[4]=array('helpdesk_visible'=>'is_helpdesk_visible');
   // For Rule::RULE_OCS_AFFECT_COMPUTER
   $changes[0]=array('FK_entities'=>'entities_id');
   // For Rule::RULE_SOFTWARE_CATEGORY
   $changes[3]=array('category'=>'softwarecategories_id','comment'=>'comment');
   // For Rule::RULE_TRACKING_AUTO_ACTION
   $changes[2]=array('category'        => 'ticketcategories_id',
                                             'author'          => 'users_id',
                                             'author_location' => 'users_locations',
                                             'FK_group'        => 'groups_id',
                                             'assign'          => 'users_id_assign',
                                             'assign_group'    => 'groups_id_assign',
                                             'device_type'     => 'itemtype',
                                             'FK_entities'     => 'entities_id',
                                             'contents'        => 'content',
                                             'request_type'    => 'requesttypes_id');

   $DB->query("SET SESSION group_concat_max_len = 9999999;");
   foreach ($changes as $ruletype => $tab) {
      // Get rules
      $query = "SELECT GROUP_CONCAT(id) FROM glpi_rules WHERE sub_type=".$ruletype." GROUP BY sub_type;";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            // Get rule string
            $rules=$DB->result($result,0,0);
            // Update actions
            foreach ($tab as $old => $new) {
               $query = "UPDATE glpi_ruleactions SET field='$new' WHERE field='$old' AND rules_id IN ($rules);";
               $DB->query($query) or die("0.78 update datas for rules actions " . $LANG['update'][90] . $DB->error());
            }
            // Update criterias
            foreach ($tab as $old => $new) {
               $query = "UPDATE glpi_rulecriterias SET criteria='$new' WHERE criteria='$old' AND rules_id IN ($rules);";
               $DB->query($query) or die("0.78 update datas for rules criterias " . $LANG['update'][90] . $DB->error());
            }
         }
      }
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_rulecachesoftwares'); // Updating schema

   $query = "ALTER TABLE `glpi_rules` CHANGE `sub_type` `sub_type` VARCHAR( 255 ) NOT NULL DEFAULT ''";
   $DB->query($query) or die("0.78 change subtype from INT(11) to VARCHAR(255) in glpi_rules " . $LANG['update'][90] . $DB->error());
   $subtypes = array (0=>'RuleOcs',1=>'RuleRight',2=>'RuleTicket',3=>'RuleSoftwareCategory',
                   4=>'RuleDictionnarySoftware',5=>'RuleDictionnaryManufacturer',
                   6=>'RuleDictionnaryComputerModel',7=>'RuleDictionnaryComputerType',
                   8=>'RuleDictionnaryMonitorModel',9=>'RuleDictionnaryMonitorType',
                   10=>'RuleDictionnaryPrinterModel',11=>'RuleDictionnaryPrinterType',
                   12=>'RuleDictionnaryPhoneModel',13=>'RuleDictionnaryPhoneType',
                   14=>'RuleDictionnaryPeripheralModel',15=>'RuleDictionnaryPeripheralType',
                   16=>'RuleDictionnaryNetworkEquipmentModel',17=>'RuleDictionnaryNetworkEquipmentType',
                   18=>'RuleDictionnaryOperatingSystem',19=>'RuleDictionnaryOperatingSystemServicePack',
                   20=>'RuleDictionnaryOperatingSystemVersion',21=>'RuleMailCollector');

   foreach ($subtypes as $old_subtype => $new_subtype) {
      $query = "UPDATE `glpi_rules` SET `sub_type`='$new_subtype' WHERE `sub_type`='$old_subtype'";
      $DB->query($query) or die("0.78 change sub_type $old_subtype in $new_subtype in glpi_rules " . $LANG['update'][90] . $DB->error());
   }

	if (FieldExists("glpi_rulecachesoftwares","ignore_ocs_import")) {
		$query = "ALTER TABLE `glpi_rulecachesoftwares` CHANGE `ignore_ocs_import` `ignore_ocs_import` CHAR( 1 ) NULL DEFAULT NULL ";
      $DB->query($query) or die("0.78 alter table glpi_rulecachesoftwares " . $LANG['update'][90] . $DB->error());
	}
	if (!FieldExists("glpi_rulecachesoftwares","is_helpdesk_visible")) {
		$query = "ALTER TABLE `glpi_rulecachesoftwares` ADD `is_helpdesk_visible` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.78 add is_helpdesk_visible in glpi_rulecachesoftwares " . $LANG['update'][90] . $DB->error());
	}

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_entities'); // Updating schema

   if (!FieldExists("glpi_entities","sons_cache")) {
      $query = "ALTER TABLE `glpi_entities` ADD `sons_cache` LONGTEXT NULL ; ";
      $DB->query($query) or die("0.78 add sons_cache field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_entities","ancestors_cache")) {
      $query = "ALTER TABLE `glpi_entities` ADD `ancestors_cache` LONGTEXT NULL ; ";
      $DB->query($query) or die("0.78 add ancestors_cache field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_configs'); // Updating schema


	if (!FieldExists("glpi_configs","default_graphtype")) {
		$query = "ALTER TABLE `glpi_configs` ADD `default_graphtype` char( 3 ) NOT NULL DEFAULT 'svg'";
      $DB->query($query) or die("0.78 add default_graphtype in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	if (FieldExists('glpi_configs', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_configs` DROP `license_deglobalisation`;";
      $DB->query($query) or die("0.78 alter clean glpi_configs table " . $LANG['update'][90] . $DB->error());
	}

   if (FieldExists("glpi_configs","use_cache")) {
      $query = "ALTER TABLE `glpi_configs`  DROP `use_cache`;";
      $DB->query($query) or die("0.78 drop use_cache in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_configs","cache_max_size")) {
      $query = "ALTER TABLE `glpi_configs`  DROP `cache_max_size`;";
      $DB->query($query) or die("0.78 drop cache_max_size in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

	if (!FieldExists("glpi_configs","default_request_type")) {
		$query = "ALTER TABLE `glpi_configs` ADD `default_request_type` INT( 11 ) NOT NULL DEFAULT 1";
      $DB->query($query) or die("0.78 add default_request_type in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_users","default_request_type")) {
		$query = "ALTER TABLE `glpi_users` ADD `default_request_type` INT( 11 ) NULL";
      $DB->query($query) or die("0.78 add default_request_type in glpi_users " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_configs","use_noright_users_add")) {
		$query = "ALTER TABLE `glpi_configs` ADD `use_noright_users_add` tinyint( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.78 add use_noright_users_add in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_budgets'); // Updating schema

	if (!FieldExists("glpi_profiles","budget")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `budget` CHAR( 1 ) NULL ";
		$DB->query($query) or die("0.78 add budget in glpi_profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`=`infocom`";
		$DB->query($query) or die("0.78 update default budget rights" . $LANG['update'][90] . $DB->error());

	}


   if (!FieldExists("glpi_budgets","is_recursive")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_recursive` tinyint(1) NOT NULL DEFAULT '0' AFTER `name`";
      $DB->query($query) or die("0.78 add is_recursive field in glpi_budgets" . $LANG['update'][90] . $DB->error());

      // budgets in 0.72 were global
      $query = "UPDATE `glpi_budgets` SET `is_recursive` = '1';";
      $DB->query($query) or die("0.78 set is_recursive to true in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","entities_id")) {
         $query = "ALTER TABLE `glpi_budgets` ADD `entities_id` int(11) NOT NULL default '0' AFTER `name`,
                                          ADD INDEX `entities_id` (`entities_id`);";
         $DB->query($query) or die("0.78 add entities_id field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","is_deleted")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                                       ADD INDEX `is_deleted` (`is_deleted`)";
      $DB->query($query) or die("0.78 add is_deleted field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","begin_date")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `begin_date` DATE NULL";
      $DB->query($query) or die("0.78 add begin_date field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","end_date")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `end_date` DATE NULL";
      $DB->query($query) or die("0.78 add end_date field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","value")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `value` DECIMAL( 20, 4 )  NOT NULL default '0.0000'";
      $DB->query($query) or die("0.78 add value field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","is_template")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_template` tinyint(1) NOT NULL default '0',
                                          ADD INDEX `is_template` (`is_template`);";
      $DB->query($query) or die("0.78 add is_template field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","template_name")) {
      $query = "ALTER TABLE `glpi_budgets`  ADD `template_name` varchar(255) default NULL";
      $DB->query($query) or die("0.78 add template_name field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","date_mod")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `date_mod`  DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";
      $DB->query($query) or die("0.78 add date_mod field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }


   if (!FieldExists("glpi_budgets","notepad")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `notepad` LONGTEXT NULL collate utf8_unicode_ci";
      $DB->query($query) or die("0.78 add notepad field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }


   // Change budget search pref : date_mod
   $ADDTODISPLAYPREF['Budget']=array(2,3,4,19);


   displayMigrationMessage("078", $LANG['update'][141] . ' - ' . $LANG['crontask'][0]); // Updating schema
   if (!TableExists('glpi_crontasks')) {
      $query = "CREATE TABLE `glpi_crontasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL COMMENT 'task name',
        `frequency` int(11) NOT NULL COMMENT 'second between launch',
        `param` int(11) DEFAULT NULL COMMENT 'task specify parameter',
        `state` int(11) NOT NULL DEFAULT '1' COMMENT '0:disabled, 1:waiting, 2:running',
        `mode` int(11) NOT NULL DEFAULT '1' COMMENT '1:internal, 2:external',
        `allowmode` int(11) NOT NULL DEFAULT '3' COMMENT '1:internal, 2:external, 3:both',
        `hourmin` int(11) NOT NULL DEFAULT '0',
        `hourmax` int(11) NOT NULL DEFAULT '24',
        `logs_lifetime` int(11) NOT NULL DEFAULT '30' COMMENT 'number of days',
        `lastrun` datetime DEFAULT NULL COMMENT 'last run date',
        `lastcode` int(11) DEFAULT NULL COMMENT 'last run return code',
        `comment` text COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`itemtype`,`name`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        COMMENT='Task run by internal / external cron.';";
      $DB->query($query) or die("0.78 create glpi_crontasks" . $LANG['update'][90] . $DB->error());

      $query="INSERT INTO `glpi_crontasks`
         (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
         VALUES
         ('OcsServer', 'ocsng', 300, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CartridgeItem', 'cartridge', 86400, 10, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('ConsumableItem', 'consumable', 86400, 10, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('SoftwareLicense', 'software', 86400, NULL, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('Contract', 'contract', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('InfoCom', 'infocom', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CronTask', 'logs', 86400, 10, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CronTask', 'optimize', 604800, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('MailCollector', 'mailgate', 600, 10, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('DBconnection', 'checkdbreplicate', 300, NULL, 0, 0, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CronTask', 'checkupdate', 604800, NULL, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CronTask', 'session', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('CronTask', 'graph', 3600, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         ('Ticket','closeticket','43200',NULL,'1','1','3','0','24','30',NULL,NULL,NULL)";
      $DB->query($query) or die("0.78 populate glpi_crontasks" . $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['Crontask']=array(8,3,4,7);
   }

   if (!TableExists('glpi_crontasklogs')) {
      $query = "CREATE TABLE `glpi_crontasklogs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `crontasks_id` int(11) NOT NULL,
        `crontasklogs_id` int(11) NOT NULL COMMENT 'id of ''start'' event',
        `date` datetime NOT NULL,
        `state` int(11) NOT NULL COMMENT '0:start, 1:run, 2:stop',
        `elapsed` float NOT NULL COMMENT 'time elapsed since start',
        `volume` int(11) NOT NULL COMMENT 'for statistics',
        `content` varchar(255) COLLATE utf8_unicode_ci NULL COMMENT 'message',
        PRIMARY KEY (`id`),
        KEY `crontasks_id` (`crontasks_id`),
        KEY `crontasklogs_id` (`crontasklogs_id`),
        KEY `crontasklogs_id_state` (`crontasklogs_id`,`state`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
      $DB->query($query) or die("0.78 create glpi_crontasklogs" . $LANG['update'][90] . $DB->error());
   }
   // Retrieve core task lastrun date
   $tasks=array('ocsng','cartridge','consumable','software','contract','infocom',
               'logs','optimize','mailgate','DBConnection','check_update','session');
   foreach ($tasks as $task) {
      $lock=GLPI_CRON_DIR. '/' . $task . '.lock';
      if (is_readable($lock) && $stat=stat($lock)) {
         $DB->query("UPDATE `glpi_crontasks` SET `lastrun`='".date('Y-m-d H:i:s',$stat['mtime'])."'
                     WHERE `name`='$task'");
         unlink($lock);
      }
   }
   // Clean plugin lock
   foreach(glob(GLPI_CRON_DIR. '/*.lock') as $lock) {
      unlink($lock);
   }

   // disable ocsng cron if not activate
   if (FieldExists('glpi_configs','use_ocs_mode')) {
      $query="SELECT `use_ocs_mode` FROM `glpi_configs` WHERE `id`=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value==0) {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='ocsng';";
               $DB->query($query);
            }
         }
      }
   }


   // Move glpi_config.expire_events to glpi_crontasks.param
   if (FieldExists('glpi_configs','expire_events')) {
      $query="SELECT `expire_events` FROM `glpi_configs` WHERE `id`=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value>0) {
               $query="UPDATE `glpi_crontasks` SET `state`='1', `param`='$value' WHERE `name`='logs';";
            } else {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='logs';";
            }
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `expire_events`";
      $DB->query($query) or die("0.78 drop expire_events in glpi_configs" . $LANG['update'][90] . $DB->error());
   }

   // Move glpi_config.auto_update_check to glpi_crontasks.state
   if (FieldExists('glpi_configs','auto_update_check')) {
      $query="SELECT `auto_update_check` FROM `glpi_configs` WHERE id=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value>0) {
               $value *= DAY_TIMESTAMP;
               $query="UPDATE `glpi_crontasks` SET `state`='1', `frequency`='$value' WHERE `name`='check_update';";
            } else {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='logs';";
            }
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `auto_update_check`";
      $DB->query($query) or die("0.78 drop auto_update_check in check_update" . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists('glpi_configs','dbreplicate_maxdelay')) {
      $query="SELECT `dbreplicate_maxdelay` FROM `glpi_configs` WHERE id=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            $value = intval($value/60);
            $query="UPDATE `glpi_crontasks` SET `state`='1', `frequency`='$value' WHERE `name`='check_dbreplicate';";
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `dbreplicate_maxdelay`";
      $DB->query($query) or die("0.78 drop dbreplicate_maxdelay in check_update" . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_configs','dbreplicate_notify_desynchronization')) {
      $query="ALTER TABLE `glpi_configs` DROP `dbreplicate_notify_desynchronization`";
      $DB->query($query) or die("0.78 drop dbreplicate_notify_desynchronization in check_update" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_configs','cron_limit')) {
      $query="ALTER TABLE `glpi_configs` ADD `cron_limit` TINYINT NOT NULL DEFAULT '1'
                           COMMENT 'Number of tasks execute by external cron'";
      $DB->query($query) or die("0.78 add cron_limit in glpi_configs" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documents','sha1sum')) {
      $query="ALTER TABLE `glpi_documents`
                   ADD `sha1sum` CHAR(40) NULL DEFAULT NULL ,
                   ADD INDEX (`sha1sum`)";
      $DB->query($query) or die("0.78 add sha1sum in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists('glpi_documents','filename')) {
        $query="ALTER TABLE `glpi_documents`
                  CHANGE `filename` `filename` VARCHAR( 255 ) NULL DEFAULT NULL
                  COMMENT 'for display and transfert'";
        $DB->query($query) or die("0.78 alter filename in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documents','filepath')) {
      $query="ALTER TABLE `glpi_documents`
                ADD `filepath` VARCHAR( 255 ) NULL
                COMMENT 'file storage path' AFTER `filename`";
      $DB->query($query) or die("0.78 add filepath in glpi_documents" . $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_documents` SET `filepath`=`filename`";
      $DB->query($query) or die("0.78 set value of filepath in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - ' . $LANG['setup'][79]); // Updating schema
   if (!FieldExists('glpi_ticketcategories','entities_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                    ADD `entities_id` INT NOT NULL DEFAULT '0' AFTER `id`,
                    ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`,
                    ADD INDEX (`entities_id`)";
      $DB->query($query) or die("0.78 add entities_id,is_recursive in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());

      // Set existing categories recursive global
      $query = "UPDATE `glpi_ticketcategories` SET `is_recursive` = '1'";
      $DB->query($query) or die("0.78 set value of is_recursive in glpi_ticketcategories" .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','knowbaseitemcategories_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                      ADD `knowbaseitemcategories_id` INT NOT NULL DEFAULT '0',
                      ADD INDEX ( `knowbaseitemcategories_id` )";

      $DB->query($query) or die("0.78 add knowbaseitemcategories_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','users_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `users_id` INT NOT NULL DEFAULT '0',
                        ADD INDEX ( `users_id` ) ";

      $DB->query($query) or die("0.78 add users_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','groups_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `groups_id` INT NOT NULL DEFAULT '0',
                        ADD INDEX ( `groups_id` ) ";

      $DB->query($query) or die("0.78 add groups_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','ancestors_cache')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `ancestors_cache` LONGTEXT NULL,
                        ADD `sons_cache` LONGTEXT NULL";

      $DB->query($query) or die("0.78 add cache in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','is_helpdeskvisible')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `is_helpdeskvisible` TINYINT( 1 ) NOT NULL DEFAULT '1'";

      $DB->query($query) or die("0.78 add cache in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }


   // change item type management for helpdesk
   if (FieldExists('glpi_profiles','helpdesk_hardware_type')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `helpdesk_item_type` TEXT NULL DEFAULT NULL AFTER `helpdesk_hardware_type` ;";
      $DB->query($query) or die("0.78 add  helpdesk_item_type in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

      $query="SELECT id, helpdesk_hardware_type FROM glpi_profiles";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_assoc($result)) {
               $types=$data['helpdesk_hardware_type'];
               $CFG_GLPI["helpdesk_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                       PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);
               $tostore=array();

               foreach($CFG_GLPI["helpdesk_types"] as $itemtype) {
                  if (pow(2,$itemtype)&$types) {
                     $tostore[]=$typetoname[$itemtype];
                  }
               }
               $query="UPDATE `glpi_profiles`
                     SET `helpdesk_item_type`='".exportArrayToDB($tostore)."'
                     WHERE `id`='".$data['id']."'";

               $DB->query($query) or die("0.78 populate helpdesk_item_type" .
                                    $LANG['update'][90] . $DB->error());
            }
         }
      }
      $query = "ALTER TABLE `glpi_profiles` DROP `helpdesk_hardware_type`;";
      $DB->query($query) or die("0.78 drop helpdesk_hardware_type in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

   }


   if (!FieldExists('glpi_profiles','helpdesk_status')) {
      $query = "ALTER TABLE `glpi_profiles`
                   ADD `helpdesk_status` TEXT NULL
                        COMMENT 'json encoded array of from/dest allowed status change'
                        AFTER `helpdesk_item_type`";
      $DB->query($query) or die("0.78 add helpdesk_status in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','update_priority')) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `update_priority` CHAR( 1 ) NULL DEFAULT NULL AFTER `update_ticket`";
      $DB->query($query) or die("0.78 add update_priority in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `update_priority`=`update_ticket`";
      $DB->query($query) or die("0.78 set update_priority in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_profiles','comment_ticket')) {
      $query = "ALTER TABLE `glpi_profiles`
                CHANGE `comment_ticket` `add_followups` CHAR(1) NULL DEFAULT NULL";
      $DB->query($query) or die("0.78 add add_followups in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_profiles','comment_all_ticket')) {
      $query = "ALTER TABLE `glpi_profiles`
                CHANGE `comment_all_ticket` `global_add_followups`  CHAR(1) NULL DEFAULT NULL";
      $DB->query($query) or die("0.78 add add_followups in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists('glpi_profiles','update_tasks')) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `global_add_tasks` CHAR( 1 ) NULL AFTER `global_add_followups`,
                ADD `update_tasks` CHAR( 1 ) NULL AFTER `update_followups`";
      $DB->query($query) or die("0.78 add global_add_tasks, update_tasks in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles`
                SET `update_tasks`=`update_followups`, `global_add_tasks`=`global_add_followups`";
      $DB->query($query) or die("0.78 set update_tasks, global_add_tasks in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_taskcategories')) {
      $query = "CREATE TABLE `glpi_taskcategories` (
           `id` int(11) NOT NULL auto_increment,
           `entities_id` int(11) NOT NULL default '0',
           `is_recursive` tinyint(1) NOT NULL default '0',
           `taskcategories_id` int(11) NOT NULL default '0',
           `name` varchar(255) default NULL,
           `completename` text,
           `comment` text,
           `level` int(11) NOT NULL default '0',
           `ancestors_cache` longtext,
           `sons_cache` longtext,
           `is_helpdeskvisible` tinyint(1) NOT NULL default '1',
           PRIMARY KEY  (`id`),
           KEY `name` (`name`),
           KEY `taskcategories_id` (`taskcategories_id`),
           KEY `entities_id` (`entities_id`)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or die("0.78 create glpi_taskcategories" . $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_ticketsolutiontypes')) {
      $query = "CREATE TABLE `glpi_ticketsolutiontypes` (
           `id` int(11) NOT NULL auto_increment,
           `name` varchar(255) default NULL,
           `comment` text,
           PRIMARY KEY  (`id`),
           KEY `name` (`name`)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or die("0.78 create glpi_ticketsolutiontypes" . $LANG['update'][90] . $DB->error());

      // Populate only required for migration of ticket status
      $query = "INSERT INTO `glpi_ticketsolutiontypes`
                (`id` ,`name` ,`comment`)
                VALUES
                ('1', '".$LANG['joblist'][17]."', NULL),
                ('2', '".$LANG['joblist'][10]."', NULL)";
      $DB->query($query) or die("0.78 populate glpi_ticketsolutiontypes" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets', 'solution')) {
      $query = "ALTER TABLE `glpi_tickets`
                  ADD `ticketsolutiontypes_id` INT( 11 ) NOT NULL DEFAULT '0',
                  ADD `solution` TEXT NULL,
                  ADD INDEX ( `ticketsolutiontypes_id` ) ";
      $DB->query($query) or die("0.78 create glpi_ticketsolutions" . $LANG['update'][90] . $DB->error());

      // Move old status "old_done"", "old_notdone" as solution
      // and change to new "solved" / "closed" status
      $query = "UPDATE `glpi_tickets`
                SET `ticketsolutiontypes_id`='2', `status`='closed'
                WHERE `status`='old_done'";
      $DB->query($query) or die("0.78 migration of glpi_tickets status" . $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_tickets`
                SET `ticketsolutiontypes_id`='1', `status`='closed'
                WHERE `status`='old_notdone'";
      $DB->query($query) or die("0.78 migration of glpi_tickets status" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documenttypes','comment')) {
      $query = "ALTER TABLE `glpi_documenttypes` ADD `comment` TEXT NULL ";
      $DB->query($query) or die("0.78 add comment in glpi_documenttypes" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_locations','is_recursive')) {
      $query = "ALTER TABLE `glpi_locations`
                        ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`,
                        ADD `ancestors_cache` LONGTEXT NULL,
                        ADD `sons_cache` LONGTEXT NULL";

      $DB->query($query) or die("0.78 add recursive, cache in glpi_locations" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists('glpi_locations','building')) {
      $query = "ALTER TABLE `glpi_locations` ADD `building` VARCHAR( 255 ) NULL ,
                                             ADD `room` VARCHAR( 255 ) NULL ";

      $DB->query($query) or die("0.78 add building, room in glpi_locations" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_requesttypes')) {
      $query="CREATE TABLE `glpi_requesttypes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `is_helpdesk_default` tinyint(1) NOT NULL DEFAULT '0',
              `is_mail_default` tinyint(1) NOT NULL DEFAULT '0',
              `comment` text COLLATE utf8_unicode_ci,
              PRIMARY KEY (`id`),
              KEY `name` (`name`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.78 create glpi_requesttypes" . $LANG['update'][90] . $DB->error());

      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(1, '".
                  addslashes($LANG['Menu'][31])."', 1, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(2, '".
                  addslashes($LANG['setup'][14])."', 0, 1, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(3, '".
                  addslashes($LANG['help'][35])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(4, '".
                  addslashes($LANG['tracking'][34])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(5, '".
                  addslashes($LANG['tracking'][35])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(6, '".
                  addslashes($LANG['common'][62])."', 0, 0, NULL)");
      // Add default display
      $ADDTODISPLAYPREF['RequestType']=array(14,15);
   }
   if (FieldExists('glpi_tickets','request_type')) {
      $query = "ALTER TABLE `glpi_tickets`
                      CHANGE `request_type` `requesttypes_id` INT( 11 ) NOT NULL DEFAULT '0'";

      $DB->query($query) or die("0.78 change requesttypes_id in glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_configs','default_request_type')) {
      $query = "ALTER TABLE `glpi_configs`
            CHANGE `default_request_type` `default_requesttypes_id` INT( 11 ) NOT NULL DEFAULT '1'";

      $DB->query($query) or die("0.78 change requesttypes_id in glpi_configs" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users','default_request_type')) {
      $query = "ALTER TABLE `glpi_users`
                CHANGE `default_request_type` `default_requesttypes_id` INT( 11 ) NULL DEFAULT NULL";

      $DB->query($query) or die("0.78 change requesttypes_id in glpi_users" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_groups','date_mod')) {
      $query = "ALTER TABLE `glpi_groups`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_groups" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","priority_matrix")) {
      $query = "ALTER TABLE `glpi_configs`
                   ADD `priority_matrix` VARCHAR( 255 ) NULL
                      COMMENT 'json encoded array for Urgence / Impact to Protority'";
      $DB->query($query) or die("0.78 add priority_matrix  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
      $matrix = array(1=>array(1=>1,2=>1,3=>2,4=>2,4=>2,5=>2),
                      2=>array(1=>1,2=>2,3=>2,4=>3,4=>3,5=>3),
                      3=>array(1=>2,2=>2,3=>3,4=>4,4=>4,5=>4),
                      4=>array(1=>2,2=>3,3=>4,4=>4,4=>4,5=>5),
                      5=>array(1=>2,2=>3,3=>4,4=>5,4=>5,5=>5));
      $matrix = exportArrayToDB($matrix);
      $query = "UPDATE `glpi_configs` SET `priority_matrix`='$matrix' WHERE `id`='1'";
      $DB->query($query) or die("0.78 set default priority_matrix  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","urgency_mask")) {
      $query = "ALTER TABLE `glpi_configs`
                      ADD `urgency_mask` INT( 11 ) NOT NULL DEFAULT '62',
                      ADD `impact_mask` INT( 11 ) NOT NULL DEFAULT '62'";
      $DB->query($query) or die("0.78 add urgency/impact_mask  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_users","priority_6")) {
      $query = "ALTER TABLE `glpi_users`
                      ADD `priority_6` CHAR( 20 ) NULL DEFAULT NULL AFTER `priority_5`";
      $DB->query($query) or die("0.78 add priority_6  in glpi_users " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","priority_6")) {
      $query = "ALTER TABLE `glpi_configs`
                       ADD `priority_6` CHAR( 20 ) NOT NULL DEFAULT '#ff5555' AFTER `priority_5`";
      $DB->query($query) or die("0.78 add priority_6  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','urgency')) {
      $query = "ALTER TABLE `glpi_tickets`
                      ADD `urgency` INT NOT NULL DEFAULT '1' AFTER `content`,
                      ADD `impact` INT NOT NULL DEFAULT '1' AFTER `urgency`,
                      ADD INDEX `urgency` (`urgency`),
                      ADD INDEX `impact` (`impact`)";
      $DB->query($query) or die("0.78 add urgency, impact to glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());

      // set default trivial values for Impact and Urgence
      $query = "UPDATE `glpi_tickets` SET `urgency` = `priority`, `impact` = `priority`";
      $DB->query($query) or die("0.78 set urgency, impact in glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());

      // Replace 'priority' (user choice un 0.72) by 'urgency' as criteria
      // Don't change "action" which is the result of user+tech evaluation.
      $query = "UPDATE `glpi_rulecriterias`
                SET `criteria`='urgency'
                WHERE `criteria`='priority'
                  AND `rules_id` IN (SELECT `id`
                                     FROM `glpi_rules`
                                     WHERE `sub_type`='RuleTicket')";
      $DB->query($query) or die("0.78 fix priority/urgency in business rules " .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_tickettasks')) {
      $query = "CREATE TABLE `glpi_tickettasks` (
                  `id` int(11) NOT NULL auto_increment,
                  `tickets_id` int(11) NOT NULL default '0',
                  `taskcategories_id` int(11) NOT NULL default '0',
                  `date` datetime default NULL,
                  `users_id` int(11) NOT NULL default '0',
                  `content` longtext collate utf8_unicode_ci,
                  `is_private` tinyint(1) NOT NULL default '0',
                  `realtime` float NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `date` (`date`),
                  KEY `users_id` (`users_id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `is_private` (`is_private`),
                  KEY `taskcategories_id` (`taskcategories_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.78 create glpi_tickettasks" . $LANG['update'][90] . $DB->error());

      // Required for migration from ticketfollowups to tickettasks - planned followups
      $query = "INSERT INTO `glpi_tickettasks`
                    (`id`, `tickets_id`, `date`, `users_id`, `content`, `is_private`, `realtime`)
                   SELECT `glpi_ticketfollowups`.`id`,
                          `glpi_ticketfollowups`.`tickets_id`,
                          `glpi_ticketfollowups`.`date`,
                          `glpi_ticketfollowups`.`users_id`,
                          `glpi_ticketfollowups`.`content`,
                          `glpi_ticketfollowups`.`is_private`,
                          `glpi_ticketfollowups`.`realtime`
                   FROM `glpi_ticketfollowups`
                   INNER JOIN `glpi_ticketplannings`
                     ON (`glpi_ticketplannings`.`ticketfollowups_id` = `glpi_ticketfollowups`.`id`)";
      $DB->query($query) or die("0.78 populate glpi_tickettasks" . $LANG['update'][90] . $DB->error());

      // delete from ticketfollowups - planned followups, previously copied
      $query = "DELETE FROM `glpi_ticketfollowups`
                WHERE `glpi_ticketfollowups`.`id` IN
                  (SELECT `glpi_ticketplannings`.`ticketfollowups_id`
                   FROM `glpi_ticketplannings`)";
      $DB->query($query) or die("0.78 delete from glpi_ticketfollowups" . $LANG['update'][90] . $DB->error());

      // Required for migration from ticketfollowups to tickettasks - followups with a duration
      $query = "INSERT INTO `glpi_tickettasks`
                    (`id`, `tickets_id`, `date`, `users_id`, `content`, `is_private`, `realtime`)
                   SELECT `glpi_ticketfollowups`.`id`,
                          `glpi_ticketfollowups`.`tickets_id`,
                          `glpi_ticketfollowups`.`date`,
                          `glpi_ticketfollowups`.`users_id`,
                          `glpi_ticketfollowups`.`content`,
                          `glpi_ticketfollowups`.`is_private`,
                          `glpi_ticketfollowups`.`realtime`
                   FROM `glpi_ticketfollowups`
                   WHERE `realtime`>0";
      $DB->query($query) or die("0.78 populate glpi_tickettasks" . $LANG['update'][90] . $DB->error());

      // delete from ticketfollowups - followups with duration, previously copied
      $query = "DELETE FROM `glpi_ticketfollowups`
                WHERE `realtime`>0";
      $DB->query($query) or die("0.78 delete from glpi_ticketfollowups" . $LANG['update'][90] . $DB->error());

      // ticketplannings is for tickettasks
      $query = "ALTER TABLE `glpi_ticketplannings`
                  CHANGE `ticketfollowups_id` `tickettasks_id` int(11) NOT NULL default '0'";
      $DB->query($query) or die("0.78 alter glpi_ticketplannings" . $LANG['update'][90] . $DB->error());

      // add requesttype for glpi_ticketfollowups
      $query = "ALTER TABLE `glpi_ticketfollowups`
                  DROP `realtime`,
                  ADD `requesttypes_id` int(11) NOT NULL default '0',
                  ADD INDEX `requesttypes_id` (`requesttypes_id`)";
      $DB->query($query) or die("0.78 alter glpi_ticketplannings" . $LANG['update'][90] . $DB->error());
   }


   // Migrate devices
   if (TableExists('glpi_computer_device')) {
      displayMigrationMessage("078", $LANG['update'][141].' - '.$LANG['title'][30]); // Updating schema

      foreach ($devtypetoname as $key => $itemtype) {
         displayMigrationMessage("078", $LANG['update'][141].' - '.$LANG['title'][30].' - '.$itemtype); // Updating schema
         $linktype="Computer_$itemtype";
         $linktable = getTableForItemType($linktype);
         $itemtable = getTableForItemType($itemtype);
         $fkname    = getForeignKeyFieldForTable($itemtable);
         $withspecifity = array(MOBOARD_DEVICE     => false,
                              PROCESSOR_DEVICE   => 'int',
                              RAM_DEVICE         => 'int',
                              HDD_DEVICE         => 'int',
                              NETWORK_DEVICE     => 'varchar',
                              DRIVE_DEVICE       => false,
                              CONTROL_DEVICE     => false,
                              GFX_DEVICE         => 'int',
                              SND_DEVICE         => false,
                              PCI_DEVICE         => false,
                              CASE_DEVICE        => false,
                              POWER_DEVICE       => false,);

         if (FieldExists($itemtable,'specif_default')) {
            // Convert default specifity
            if ($withspecifity[$key]) {
               // Convert data to int
               if ($withspecifity[$key] == 'int') {
                  // clean non integer values
                  $query="UPDATE `$itemtable` SET `specif_default` = 0
                             WHERE `specif_default` NOT REGEXP '^[0-9]*$' OR `specif_default` = '' OR `specif_default` IS NULL";
                  $DB->query($query) or die("0.78 update specif_default in $itemtable " . $LANG['update'][90] . $DB->error());

                  $query = "ALTER TABLE `$itemtable` CHANGE `specif_default` `specif_default` INT(11) NOT NULL";
                  $DB->query($query) or die("0.78 alter specif_default in $itemtable " . $LANG['update'][90] . $DB->error());
               }
            } else { // Drop default specificity
               $query = "ALTER TABLE `$itemtable` DROP `specif_default`";
               $DB->query($query) or die("0.78 drop specif_default in $itemtable " . $LANG['update'][90] . $DB->error());
            }
         }

         if (!TableExists($linktable)) {
            // create table
            $query = "CREATE TABLE `$linktable` (
                        `id` int(11) NOT NULL auto_increment,
                        `computers_id` int(11) NOT NULL default '0',
                        `$fkname` int(11) NOT NULL default '0',";
            if ($withspecifity[$key]) {
               if ($withspecifity[$key] == 'int') {
                  $query.="`specificity` int(11) NOT NULL,";

               } else {
                  $query.="`specificity` varchar(255) collate utf8_unicode_ci default NULL,";
               }
            }
            $query.="PRIMARY KEY  (`id`),
                     KEY `computers_id` (`computers_id`),
                     KEY `$fkname` (`$fkname`)";
            if ($withspecifity[$key]) {
               $query.=",KEY `specificity` (`specificity`)";
            }
            $query.=") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die("0.78 create $linktable " . $LANG['update'][90] . $DB->error());

            // Update data before copy
            if ($withspecifity[$key]) {
               // Convert data to int
               if ($withspecifity[$key] == 'int') {
                  // clean non integer values
                  $query="UPDATE `glpi_computer_device` SET `specificity` = 0
                        WHERE device_type=$key
                           AND `specificity` NOT REGEXP '^[0-9]*$' OR `specificity` = ''";
                  $DB->query($query) or die("0.78 update specificity in glpi_computer_device for $itemtype" . $LANG['update'][90] . $DB->error());
               }
            }
            // copy datas to new table : keep id for ocs sync
            $query="INSERT INTO `$linktable` (`id`,`computers_id`,`$fkname`
                     ".($withspecifity[$key]?",`specificity`":'').")
                        SELECT ID, FK_computers,FK_device".($withspecifity[$key]?",specificity":'')."
                        FROM glpi_computer_device
                        WHERE device_type=$key";
            $DB->query($query) or die("0.78 populate $linktable " . $LANG['update'][90] . $DB->error());
         }
      }
      // Drop computer_device_table
      $query="DROP TABLE `glpi_computer_device`";
      $DB->query($query) or die("0.78 drop glpi_computer_device " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_users','task_private')) {
      $query = "ALTER TABLE `glpi_users`
                ADD `task_private` TINYINT(1) DEFAULT NULL AFTER `followup_private`";
      $DB->query($query) or die("0.78 add task_private to glpi_users" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_configs','task_private')) {
      $query = "ALTER TABLE `glpi_configs`
                ADD `task_private` TINYINT(1) NOT NULL DEFAULT '0' AFTER `followup_private`";
      $DB->query($query) or die("0.78 add task_private to glpi_users" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_rules','date_mod')) {
      $query = "ALTER TABLE `glpi_rules`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_rules" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_authldaps','entity_field')) {
      $query = "ALTER TABLE `glpi_authldaps` ADD `entity_field` VARCHAR( 255 ) DEFAULT NULL";
      $DB->query($query) or die("0.78 add entity_field to glpi_authldaps" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_authldaps','entity_condition')) {
      $query = "ALTER TABLE `glpi_authldaps` ADD `entity_condition`  TEXT NULL collate utf8_unicode_ci";
      $DB->query($query) or die("0.78 add entity_condition to glpi_authldaps" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists ('glpi_entitydatas','ldapservers_id')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `ldapservers_id` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 add ldapservers_id to glpi_entitydatas" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists ('glpi_entitydatas','mail_domain')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `mail_domain` VARCHAR( 255 ) DEFAULT NULL";
      $DB->query($query) or die("0.78 add mail_domain to glpi_entitydatas" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists ('glpi_entitydatas','entity_ldapfilter')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `entity_ldapfilter`  TEXT NULL collate utf8_unicode_ci";
      $DB->query($query) or die("0.78 add entity_ldapfilter to glpi_entitydatas" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','import_externalauth_users')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `import_externalauth_users` CHAR( 1 ) NULL";
      $DB->query($query) or die("0.78 add import_externalauth_users in glpi_profiles". $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `import_externalauth_users`='w' WHERE `user` ='w'";
      $DB->query($query) or die("0.78 add import_externalauth_users right users which are able to write users " . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][141].' - '.$LANG['setup'][704]); // Updating schema
   $templates = array();
   if (!TableExists('glpi_notificationtemplates')) {
      $query = "CREATE TABLE `glpi_notificationtemplates` (
                 `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
                 `name` VARCHAR( 255 ) default NULL ,
                 `itemtype` VARCHAR( 100 ) NOT NULL,
                 `date_mod` DATETIME DEFAULT NULL ,
                 `comment` text collate utf8_unicode_ci,
                 PRIMARY KEY ( `ID` )
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.78 create glpi_notificationtemplates" . $LANG['update'][90] . $DB->error());

      $queries['DBConnection'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'MySQL Synchronization', 'DBConnection', '2010-02-01 15:51:46','');";
      $queries['Reservation'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Reservations', 'Reservation', '2010-02-03 14:03:45','');";
      $queries['Ticket'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Tickets', 'Ticket', '2010-02-07 21:39:15','');";
      $queries['Ticket2'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Tickets (Simple)', 'Ticket', '2010-02-07 21:39:15','');";
      $queries['TicketValidation'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Tickets Validation', 'Ticket', '2010-02-26 21:39:15','');";
      $queries['Cartridge'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Cartridges', 'Cartridge', '2010-02-16 13:17:24','');";
      $queries['Consumable'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Consumables', 'Consumable', '2010-02-16 13:17:38','');";
      $queries['Infocom'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Infocoms', 'Infocom', '2010-02-16 13:17:55','');";
      $queries['SoftwareLicense'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Licenses', 'SoftwareLicense', '2010-02-16 13:18:12','');";
      $queries['Contract'] = "INSERT INTO `glpi_notificationtemplates`
               VALUES(NULL, 'Contracts', 'Contract', '2010-02-16 13:18:12','');";
      foreach ($queries as $itemtype => $query) {
         $DB->query($query) or die("0.78 insert notification template for $itemtype " . $LANG['update'][90] . $DB->error());
         switch ($itemtype) {
            default:
               $query_id = "SELECT `id`
                            FROM `glpi_notificationtemplates`
                            WHERE `itemtype`='$itemtype'";
               break;
            case 'Ticket' :
               $query_id = "SELECT `id`
                            FROM `glpi_notificationtemplates`
                            WHERE `itemtype`='Ticket' AND `name`='Tickets'";
               break;
            case 'Ticket2' :
               $query_id = "SELECT `id`
                            FROM `glpi_notificationtemplates`
                            WHERE `itemtype`='Ticket' AND `name`='Tickets (Simple)'";
               break;
            case 'TicketValidation' :
               $query_id = "SELECT `id`
                            FROM `glpi_notificationtemplates`
                            WHERE `itemtype`='Ticket' AND `name`='Tickets Validation'";
               break;
         }
         $result = $DB->query($query_id) or die ($DB->error());
         $templates[$itemtype] = $DB->result($result,0,'id');
      }

      $ADDTODISPLAYPREF['NotificationTemplate']=array(4,16);
   }

   if (!TableExists('glpi_notificationtemplatetranslations')) {
      $query = "CREATE TABLE `glpi_notificationtemplatetranslations` (
            `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
            `notificationtemplates_id` INT( 11 ) NOT NULL DEFAULT '0',
            `language` CHAR ( 5 ) NOT NULL DEFAULT '',
            `subject` VARCHAR( 255 ) NOT NULL ,
            `content_text` TEXT NULL ,
            `content_html` TEXT NULL ,
            PRIMARY KEY ( `id` )
            )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.78 create glpi_notificationtemplatetranslations" . $LANG['update'][90] . $DB->error());

      $queries = array();
      $queries['DBConnection'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                                 VALUES(NULL, ".$templates['DBConnection'].", '','##lang.dbconnection.title##',
                        '##lang.dbconnection.delay## : ##dbconnection.delay##\r\n',
                        '&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;');";

      $content_text_reservation="======================================================================\r\n".
                                 "##lang.reservation.user##: ##reservation.user##\r\n".
                                 "##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##\r\n".
                                 "##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##\r\n".
                                 "##lang.reservation.begin##: ##reservation.begin##\r\n".
                                 "##lang.reservation.end##: ##reservation.end##\r\n".
                                 "##lang.reservation.comment##: ##reservation.comment##\r\n".
                                 "======================================================================\r\n";
      $content_html_reservation = "&lt;!-- description{ color: inherit; background: #ebebeb;".
                                 "border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }".
                                 " --&gt;\r\n&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;".
                                 "##lang.reservation.user##:&lt;/span&gt;##reservation.user##".
                                 "&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;".
                                 "##lang.reservation.item.name##:&lt;/span&gt;".
                                 "##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;".
                                 "##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech##".
                                 "##ENDIFreservation.tech##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;".
                                 "##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##".
                                 "&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;".
                                 "##lang.reservation.end##:&lt;/span&gt;".
                                 "##reservation.end##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;".
                                 "##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##".
                                 "&lt;/p&gt;";

      $queries['Reservation'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                                        VALUES(NULL,
                                                 ".$templates['Reservation'].",
                                                    '',
                                                    '##reservation.action##',
                                                   '$content_text_reservation',
                                                   '$content_html_reservation');";

      $queries['Ticket'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                                    VALUES(NULL, '".$templates['Ticket']."', '',
                                    '##ticket.action## ##ticket.title##',
                                    '##lang.ticket.url## : ##ticket.url## \r\n
                                  ##lang.ticket.description## \r\n\n
                                  ##lang.ticket.title## &#160;:##ticket.title##\n
                                  ##lang.ticket.author.name##
                                  ##IFticket.author.name##
                                  ##ticket.author.name## ##ENDIFticket.author.name##
                                  ##ELSEticket.author.name##--##ENDELSEticket.author.name##\n
                                  ##lang.ticket.creationdate## &#160;:##ticket.creationdate##\n
                                  ##lang.ticket.closedate## &#160;:##ticket.closedate##\n
                                  ##lang.ticket.requesttype## &#160;:##ticket.requesttype##\n
                                  ##IFticket.itemtype## ##lang.ticket.item.name## &#160;: ##ticket.itemtype## - ##ticket.item.name##
                                  ##IFticket.item.model## - ##ticket.item.model## ##ENDIFticket.item.model##
                                  ##IFticket.item.serial## -##ticket.item.serial## ##ENDIFticket.item.serial##
                                 &#160; ##IFticket.item.otherserial## -##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype##\n
                                  ##IFticket.assigntouser## ##lang.ticket.assigntouser## &#160;: ##ticket.assigntouser## ##ENDIFticket.assigntouser##\n
                                  ##lang.ticket.status## &#160;: ##ticket.status##\n
                                  ##IFticket.assigntogroup## ##lang.ticket.assigntogroup## &#160;: ##ticket.assigntogroup## ##ENDIFticket.assigntogroup##\n
                                  ##lang.ticket.urgency## &#160;: ##ticket.urgency##\n
                                  ##lang.ticket.impact## &#160;: ##ticket.impact##\n
                                  ##lang.ticket.priority## &#160;: ##ticket.priority## \n
                                  ##IFticket.user.email## ##lang.ticket.user.email## &#160;: ##ticket.user.email ##ENDIFticket.user.email## \n
                                  ##IFticket.category## ##lang.ticket.category## &#160;:##ticket.category## ##ENDIFticket.category##
                                  ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category## \n
                                  ##lang.ticket.content## &#160;: ##ticket.content## \r\n
                                  ##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups## \r\n\n
                                 ##FOREACHfollowups## \r\n \n [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate## \n
                                  ##lang.followup.author## ##followup.author##\n ##lang.followup.description## ##followup.description##\n
                                  ##lang.followup.date## ##followup.date##\n ##lang.followup.requesttype## ##followup.requesttype## \r\n\n
                                 ##ENDFOREACHfollowups## \r\n ##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks## \r\n\n##FOREACHtasks## \r\n \n
                                  [##task.date##] ##lang.task.isprivate## : ##task.isprivate## \n ##lang.task.author## ##task.author##\n
                                  ##lang.task.description## ##task.description##\n ##lang.task.time## ##task.time##\n ##lang.task.category## ##task.category## \r\n\n
                                 ##ENDFOREACHtasks##',
                                  '&lt;!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }
                                  --&gt;\r\n&lt;div&gt;##lang.ticket.url## : &lt;a href=''##ticket.url##
                                 ''&gt;##ticket.url##&lt;/a&gt;&lt;/div&gt;\r\n
                                 &lt;div class=\"description b\"&gt;##lang.ticket.description##
                                 &lt;/div&gt;\r\n&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                  ##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
                                 &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.author.name##&lt;/span&gt; ##IFticket.author.name## ##ticket.author.name## ##ENDIFticket.author.name##
                                  ##ELSEticket.author.name##--##ENDELSEticket.author.name##
                                 &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.creationdate##&lt;/span&gt;&#160;:##ticket.creationdate##
                                 &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.closedate##&lt;/span&gt;&#160;:##ticket.closedate##
                                 &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.requesttype##&lt;/span&gt;&#160;:##ticket.requesttype##&lt;br /&gt;
                                  ##IFticket.itemtype## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.item.name##&lt;/span&gt;&#160;: ##ticket.itemtype## - ##ticket.item.name##
                                  ##IFticket.item.model## - ##ticket.item.model##
                                   ##ENDIFticket.item.model## ##IFticket.item.serial## -##ticket.item.serial## ##ENDIFticket.item.serial##&#160;
                                 ##IFticket.item.otherserial## -##ticket.item.otherserial##  ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype##
                                 &lt;br /&gt; ##IFticket.assigntouser## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.assigntouser##&lt;/span&gt;&#160;: ##ticket.assigntouser## ##ENDIFticket.assigntouser##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.ticket.status##
                                 &lt;/span&gt;&#160;: ##ticket.status##&lt;br /&gt; ##IFticket.assigntogroup## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.assigntogroup##&lt;/span&gt;&#160;: ##ticket.assigntogroup## ##ENDIFticket.assigntogroup##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.urgency##&lt;/span&gt;&#160;: ##ticket.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.impact##&lt;/span&gt;&#160;: ##ticket.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.priority##&lt;/span&gt;&#160;: ##ticket.priority## &lt;br /&gt; ##IFticket.user.email##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.user.email##&lt;/span&gt;&#160;: ##ticket.user.email ##ENDIFticket.user.email##
                                  &lt;br /&gt; ##IFticket.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.ticket.category##
                                 &lt;/span&gt;&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
                                  &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.ticket.content##&lt;/span&gt;&#160;: ##ticket.content##&lt;/p&gt;\r\n&lt;div class=\"description b\"&gt;
                                 ##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##
                                 &lt;/div&gt;\r\n&lt;p&gt;##FOREACHfollowups##&lt;/p&gt;\r\n&lt;div class=\"description b\"&gt;&lt;br /&gt; &lt;strong&gt;
                                 [##followup.date##] &lt;em&gt;##lang.followup.isprivate## : ##followup.isprivate##
                                 &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.followup.author##
                                 &lt;/span&gt; ##followup.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.followup.description##
                                 &lt;/span&gt; ##followup.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.followup.date##
                                 &lt;/span&gt; ##followup.date##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.followup.requesttype##
                                 &lt;/span&gt; ##followup.requesttype##&lt;/div&gt;\r\n&lt;p&gt;
                                 ##ENDFOREACHfollowups##&lt;/p&gt;\r\n&lt;div class=\"description b\"&gt;
                                 ##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##
                                 &lt;/div&gt;\r\n&lt;p&gt;
                                 ##FOREACHtasks##&lt;/p&gt;\r\n&lt;div class=\"description b\"&gt;&lt;br /&gt; &lt;strong&gt;
                                 [##task.date##] &lt;em&gt;##lang.task.isprivate## : ##task.isprivate##
                                 &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                                 ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;\r\n&lt;p&gt;
                                 ##ENDFOREACHtasks##&lt;/p&gt;');";

      $queries['Contract'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                              VALUES(NULL, ".$templates['Contract'].", '',
                               '##contract.action##  ##contract.entity##',
                               '##lang.contract.entity## :##contract.entity##\r\n\
                                r\n##FOREACHcontracts##\r\n
                                ##lang.contract.name## : ##contract.name##\r\n
                                ##lang.contract.number## : ##contract.number##\r\n
                                ##lang.contract.time## : ##contract.time##\r\n
                                ##IFcontract.type####lang.contract.type## : ##contract.type## ##ENDIFcontract.type##\r\n
                                ##contract.url##\r\n
                                ##ENDFOREACHcontracts##',
                               '&lt;p&gt;##lang.contract.entity## :##contract.entity##&lt;br /&gt;
                                &lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## : ##contract.name##&lt;br /&gt;
                                ##lang.contract.number## : ##contract.number##&lt;br /&gt;
                                ##lang.contract.time## : ##contract.time##&lt;br /&gt;
                                ##IFcontract.type####lang.contract.type## : ##contract.type## ##ENDIFcontract.type##&lt;br /&gt;
                                &lt;a href=\"##contract.url##\"&gt;
                                ##contract.url##&lt;/a&gt;&lt;br /&gt;
                                ##ENDFOREACHcontracts##&lt;/p&gt;');";

      $queries['Ticket2'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                             VALUES(NULL, ".$templates['Ticket2'].", '',
                            '##ticket.action## ##ticket.title##',
                            '##lang.ticket.url## : ##ticket.url## \r\n
                             ##lang.ticket.description## \r\n\n
                             ##lang.ticket.title## &#160;:##ticket.title## \n
                             ##lang.ticket.author.name## ##IFticket.author.name##
                             ##ticket.author.name## ##ENDIFticket.author.name##
                             ##ELSEticket.author.name##--##ENDELSEticket.author.name## &#160; \n
                             ##IFticket.category## ##lang.ticket.category## &#160;:##ticket.category##
                             ##ENDIFticket.category## ##ELSEticket.category##
                             ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##\n
                             ##lang.ticket.content## &#160;: ##ticket.content##\n##IFticket.itemtype##
                             ##lang.ticket.item.name## &#160;: ##ticket.itemtype## - ##ticket.item.name##
                             ##ENDIFticket.itemtype##',
                            '&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.url##\"&gt;
                             ##ticket.url##&lt;/a&gt;&lt;/div&gt;\r\n&lt;div class=\"description b\"&gt;
                             ##lang.ticket.description##&lt;/div&gt;\r\n&lt;p&gt;&lt;span
                             style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                             ##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
                             &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                             ##lang.ticket.author.name##&lt;/span&gt;
                             ##IFticket.author.name## ##ticket.author.name##
                             ##ENDIFticket.author.name##
                             ##ELSEticket.author.name##--##ENDELSEticket.author.name##
                             &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;&#160
                            ;&lt;/span&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;
                            ##IFticket.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                            ##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##
                            ##ENDIFticket.category## ##ELSEticket.category##
                            ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
                            &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                            ##lang.ticket.content##&lt;/span&gt;&#160;:
                            ##ticket.content##&lt;br /&gt;##IFticket.itemtype##
                            &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
                            ##lang.ticket.item.name##&lt;/span&gt;&#160;:
                            ##ticket.itemtype## - ##ticket.item.name##
                            ##ENDIFticket.itemtype##&lt;/p&gt;');";

      $queries['TicketValidation'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                             VALUES(NULL, ".$templates['TicketValidation'].", '',
                            '##ticket.action## ##ticket.title##',
                            '##FOREACHvalidations##
                           ##lang.validation.title##
                           ##lang.ticket.url## : ##validation.url##

                           ##IFvalidation.status## ##lang.validation.validationstatus## ##ENDIFvalidation.status##
                           ##IFvalidation.commentvalidation##
                           ##lang.validation.commentvalidation## :  ##validation.commentvalidation##
                           ##ENDIFvalidation.commentvalidation##
                           ##ENDFOREACHvalidations##',
                            '&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
                           &lt;div&gt;##lang.validation.title##&lt;/div&gt;
                           &lt;div&gt;##lang.ticket.url## : &lt;a href=\"##validation.url##\"&gt; ##validation.url##
                           &lt;/a&gt;&lt;/div&gt;&lt;p&gt;##IFvalidation.status## ##lang.validation.validationstatus##
                           ##ENDIFvalidation.status##&lt;br  /&gt;
                           ##IFvalidation.commentvalidation##&lt;br /&gt;
                           ##lang.validation.commentvalidation## :&#160; ##validation.commentvalidation##&lt;br /&gt;
                           ##ENDIFvalidation.commentvalidation##&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;');";

      $queries['Consumable'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                           VALUES(NULL, ".$templates['Consumable'].", '',
                           '##consumable.action##  ##consumable.entity##',
                           '##lang.consumable.entity## :##consumable.entity##\n \n
                           ##FOREACHconsumables##\n##lang.consumable.item## : ##consumable.item##\n \n
                           ##lang.consumable.reference## : ##consumable.reference##\n
                           ##lang.consumable.remaining## : ##consumable.remaining##\n
                           ##consumable.url## \n
                           ##ENDFOREACHconsumables##', '&lt;p&gt;
                           ##lang.consumable.entity## :##consumable.entity##
                           &lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
                           &lt;br /&gt;##lang.consumable.item##  : ##consumable.item##&lt;br /&gt;
                           &lt;br /&gt;##lang.consumable.reference##  : ##consumable.reference##&lt;br /&gt;
                           ##lang.consumable.remaining## :  ##consumable.remaining##&lt;br /&gt;
                           &lt;a href=\"##contract.url##\"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
                            ##ENDFOREACHconsumables##&lt;/p&gt;');";

      $queries['Cartridge'] = "INSERT INTO `glpi_notificationtemplatetranslations`
                               VALUES(NULL, ".$templates['Cartridge'].", '',
                               '##cartridge.action##  ##cartridge.entity##',
                               '##lang.cartridge.entity## :##cartridge.entity##\n \n
                                ##FOREACHcartridges##\n##lang.cartridge.item## : ##cartridge.item##\n \n
                                ##lang.cartridge.reference## : ##cartridge.reference##\n
                                ##lang.cartridge.remaining## : ##cartridge.remaining##\n
                                ##cartridge.url## \n ##ENDFOREACHcartridges##',
                                '&lt;p&gt;##lang.cartridge.entity## :##cartridge.entity##
                                 &lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
                                 &lt;br /&gt;##lang.cartridge.item##   :
                                 ##cartridge.item##&lt;br /&gt; &lt;br /&gt;
                                 ##lang.cartridge.reference##  :
                                 ##cartridge.reference##&lt;br /&gt;
                                 ##lang.cartridge.remaining## :
                                 ##cartridge.remaining##&lt;br /&gt;
                                 &lt;a href=\"##contract.url##\"&gt;
                                 ##cartridge.url##&lt;/a&gt;&lt;br /&gt;
                                 ##ENDFOREACHcartridges##&lt;/p&gt;');
";
      foreach ($queries as $itemtype => $query) {
         //echo $query."<br>";
         $DB->query($query) or die("0.78 insert notification template default translation
                                             for $itemtype " . $LANG['update'][90] . $DB->error());
      }

      unset($queries);
   }

   $notifications = array();
   if (!TableExists('glpi_notifications')) {
      $query = "CREATE TABLE `glpi_notifications` (
                  `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
                  `name` VARCHAR( 255 ) DEFAULT NULL ,
                  `entities_id` INT( 11 ) NOT NULL DEFAULT '0',
                  `itemtype` VARCHAR( 100 ) NOT NULL ,
                  `event` VARCHAR( 255 ) NOT NULL ,
                  `mode` VARCHAR( 255 ) NOT NULL ,
                  `notificationtemplates_id` INT( 11 ) NOT NULL DEFAULT '0',
                  `comment` TEXT DEFAULT NULL ,
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',
                 `date_mod` DATETIME DEFAULT NULL ,
                  PRIMARY KEY ( `id` )
                  ) ENGINE = MYISAM CHARSET utf8 COLLATE utf8_unicode_ci;";
      $DB->query($query) or die("0.78 create glpi_notifications" . $LANG['update'][90] . $DB->error());

      $queries = array();
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'New Ticket', 0, 'Ticket', 'new',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Update Ticket', 0, 'Ticket', 'update',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Close Ticket', 0, 'Ticket', 'closed',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Add Followup', 0, 'Ticket', 'add_followup',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Add Task', 0, 'Ticket', 'add_task',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Ticket Solved', 0, 'Ticket', 'solved',
                                       'mail',".$templates['Ticket'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Ticket Validation', 0, 'Ticket', 'validation',
                                       'mail',".$templates['TicketValidation'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'New Reservation', 0, 'Reservation', 'new',
                                       'mail',".$templates['Reservation'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Update Reservation', 0, 'Reservation', 'update',
                                       'mail',".$templates['Reservation'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Delete Reservation', 0, 'Reservation', 'delete',
                                       'mail',".$templates['Reservation'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Contract Notice', 0, 'Contract', 'notice',
                                       'mail',".$templates['Contract'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Contract End', 0, 'Contract', 'end',
                                       'mail',".$templates['Contract'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'MySQL Synchronization', 0, 'DBConnection', 'desynchronization',
                                       'mail',".$templates['DBConnection'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Cartridges', 0, 'Cartridge', 'alert',
                                       'mail',".$templates['Cartridge'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Consumables', 0, 'Consumable', 'alert',
                                       'mail',".$templates['Consumable'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Infocoms', 0, 'Infocom', 'alert',
                                       'mail',".$templates['Infocom'].",
                                       '', 1, '2010-02-16 16:41:39');";
      $queries[] = "INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Software Licenses', 0, 'SoftwareLicense', 'alert',
                                       'mail',".$templates['SoftwareLicense'].",
                                       '', 1, '2010-02-16 16:41:39');";
      foreach($queries as $query) {
         $DB->query($query) or die("0.78 insert notification" . $LANG['update'][90] . $DB->error());
      }


      $ADDTODISPLAYPREF['Notification']=array(5,2,4,80,86);
      unset($queries);
   }


   if (!TableExists('glpi_notificationtargets') && TableExists('glpi_mailingsettings')) {
      $query = "RENAME TABLE `glpi_mailingsettings`  TO `glpi_notificationtargets`;";
      $DB->query($query) or die("0.78 rename table glpi_mailingsettings in glpi_notificationtargets" . $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_notificationtargets` ADD `notifications_id` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 add field notifications_id to glpi_notificationtargets" . $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_notificationtargets` CHANGE `type` `oldtype` VARCHAR( 255 )
                     CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL";
      $DB->query($query) or die("0.78 change field type in oldtype" . $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_notificationtargets` CHANGE `mailingtype` `type`
                     INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 change field mailingtype in type" . $LANG['update'][90] . $DB->error());

      $fields = array ('new'=>array('itemtype'=>'Ticket','newaction'=>'new'),
                       'update'=>array('itemtype'=>'Ticket','newaction'=>'update'),
                       'finish'=>array('itemtype'=>'Ticket','newaction'=>'closed'),
                       'resa'=>array('itemtype'=>'Reservation','newaction'=>'new'),
                       'followup'=>array('itemtype'=>'Ticket','newaction'=>'add_followup'),
                       'alertconsumable'=>array('itemtype'=>'Consumable','newaction'=>'alert'),
                       'alertcartridge'=>array('itemtype'=>'Cartridge','newaction'=>'alert'),
                       'alertlicense'=>array('itemtype'=>'SoftwareLicense','newaction'=>'alert'),
                       'alertinfocom'=>array('itemtype'=>'Infocom','newaction'=>'alert'),
                       'alertcontract'=>array('itemtype'=>'Contract','newaction'=>'end'));

      $query = "SELECT `oldtype` FROM `glpi_notificationtargets` GROUP BY `oldtype`";
      foreach ($DB->request($query) as $data) {

         $infos = $fields[$data['oldtype']];
         $query_type = "SELECT `id`
                        FROM `glpi_notifications`
                        WHERE `itemtype`='".$infos['itemtype']."' AND `event`='".$infos['newaction']."'";

         $result = $DB->query($query_type) or die("0.78 get notificationtargets_id " . $LANG['update'][90] . $DB->error());

         if ($DB->numrows($result)) {
            $id = $DB->result($result,0,'id');
            $query_update = "UPDATE `glpi_notificationtargets`
                             SET `notifications_id`='$id'
                             WHERE `oldtype`='".$data['oldtype']."'";
            $DB->query($query_update) or die("0.78 set notificationtargets_id " . $LANG['update'][90] . $DB->error());
         }
      }
      $query ="ALTER TABLE `glpi_notificationtargets` DROP INDEX `unicity` ";
      $DB->query($query) or die("0.78 drop index unicity from glpi_notificationtargets" . $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_notificationtargets` DROP `oldtype`";
      $DB->query($query) or die("0.78 drop field oldtype in glpi_notificationtargets" . $LANG['update'][90] . $DB->error());

      //Add administrator as target for MySQL Synchronization notification
      $query_type = "SELECT `id` FROM `glpi_notifications` WHERE `itemtype`='DBConnection'";
      $result = $DB->query($query_type) or die("0.78 get notificationtargets_id " . $LANG['update'][90] . $DB->error());

      if ($DB->numrows($result)) {
         $id = $DB->result($result,0,'id');
         $query = "INSERT INTO `glpi_notificationtargets`
                     (`id`, `notifications_id`, `type`, `items_id`)
                     VALUES (NULL, ".$id.", 1, 1);";
          $DB->query($query) or die("0.78 add target for dbsynchronization " . $LANG['update'][90] . $DB->error());
      }

      //Manage Reservation update & delete
      $query_type = "SELECT `id`
                     FROM `glpi_notifications`
                     WHERE `itemtype`='Reservation'
                        AND `event` IN ('update', 'delete')";
      foreach ($DB->request($query_type) as $data_resa) {

        $query_targets = "SELECT `glpi_notificationtargets` . *
                        FROM `glpi_notifications` , `glpi_notificationtargets`
                        WHERE `glpi_notifications`.`itemtype` = 'Reservation'
                           AND `glpi_notifications`.`event` = 'new'
                              AND `glpi_notificationtargets`.notifications_id =
                                    `glpi_notifications`.id";

         foreach ($DB->request($query_targets) as $data_targets) {
            $query_insert = "INSERT INTO `glpi_notificationtargets`
                        (`id`, `notifications_id`, `type`, `items_id`)
                        VALUES (NULL, ".$data_resa['id'].
                                ", ".$data_targets['type'].", ".
                                $data_targets['items_id'].");";
             $DB->query($query_insert) or die("0.78 add target for reservations " .
                                                 $LANG['update'][90] . $DB->error());
         }
      }

      //Manage contract notice
      $query_type = "SELECT `id`
                     FROM `glpi_notifications`
                     WHERE `itemtype`='Contract'
                        AND `event`='notice'";
      foreach ($DB->request($query_type) as $data_contract) {

        $query_targets = "SELECT `glpi_notificationtargets` . *
                        FROM `glpi_notifications` , `glpi_notificationtargets`
                        WHERE `glpi_notifications`.`itemtype` = 'Contract'
                           AND `glpi_notifications`.`event` = 'end'
                              AND `glpi_notificationtargets`.notifications_id =
                                    `glpi_notifications`.id";

         foreach ($DB->request($query_targets) as $data_targets) {
            $query_insert = "INSERT INTO `glpi_notificationtargets`
                        (`id`, `notifications_id`, `type`, `items_id`)
                        VALUES (NULL, ".$data_contract['id'].
                                ", ".$data_targets['type'].", ".
                                $data_targets['items_id'].");";
             $DB->query($query_insert) or die("0.78 add target for contract " .
                                                 $LANG['update'][90] . $DB->error());
         }
      }
   }

   if (!FieldExists('glpi_profiles','notification')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `notification` CHAR( 1 ) NULL";
      $DB->query($query) or die("0.78 add notification in glpi_profiles". $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `notification`='w' WHERE `config` ='w'";
      $DB->query($query) or die("0.78 add notification write right user which have config right " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_entitydatas','mailing_signature')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `mailing_signature` TEXT DEFAULT NULL ,
                                 ADD `cartridges_alert_repeat` INT( 11 ) NOT NULL DEFAULT '0',
                                 ADD `consumables_alert_repeat` INT( 11 ) NOT NULL DEFAULT '0',
                                 ADD `use_licenses_alert` TINYINT( 1 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 add notifications fields in glpi_entitydatas" . $LANG['update'][90] . $DB->error());
   }

   if (TableExists('glpi_mailingsettings')) {
      $query = "DROP TABLE `glpi_mailingsettings`;";
      $DB->query($query) or die("0.78 drop table glpi_mailingsettings" . $LANG['update'][90] . $DB->error());
   }

   // Migrate infocoms entity information
   if (!FieldExists('glpi_infocoms','entities_id')) {
      displayMigrationMessage("078", $LANG['update'][141].' - '.$LANG['financial'][3]); // Updating schema

      $query = "ALTER TABLE `glpi_infocoms` ADD `entities_id` int(11) NOT NULL DEFAULT 0 AFTER `itemtype`,
                        ADD `is_recursive` tinyint(1) NOT NULL DEFAULT 0 AFTER `entities_id`,
                        ADD INDEX `entities_id` ( `entities_id` )";
      $DB->query($query) or die("0.78 add entities_id and is_recursive in glpi_infocoms". $LANG['update'][90] . $DB->error());


      $entities=getAllDatasFromTable('glpi_entities');
      $entities[0]="Root";

      $query = "SELECT DISTINCT itemtype FROM glpi_infocoms";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               displayMigrationMessage("078", $LANG['update'][141].' - '.$LANG['financial'][3].' - '.$data['itemtype']); // Updating schema

               $itemtable=getTableForItemType($data['itemtype']);
               // ajout d'un contrôle pour voir si la table existe ( cas migration plugin non fait)
               if (!TableExists($itemtable)) {
                  if ($output) {
                     echo "<p class='red'>*** Skip : no table $itemtable ***</p>";
                  }
                  continue;
               }
               $do_recursive=false;
               if (FieldExists($itemtable,'is_recursive')) {
                  $do_recursive=true;
               }
               // This is duplicated in Plugin::migrateItemType() for plugin object
               foreach ($entities as $entID => $val) {
                  if ($do_recursive) {
                     // Non recursive ones
                     $query3="UPDATE `glpi_infocoms`
                              SET `entities_id`=$entID, `is_recursive`=0
                              WHERE `itemtype`='".$data['itemtype']."'
                                 AND `items_id` IN (SELECT `id` FROM `$itemtable`
                                 WHERE `entities_id`=$entID AND `is_recursive`=0)";
                     $DB->query($query3) or die("0.78 update entities_id and is_recursive=0
                           in glpi_infocoms for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());

                     // Recursive ones
                     $query3="UPDATE `glpi_infocoms`
                              SET `entities_id`=$entID, `is_recursive`=1
                              WHERE `itemtype`='".$data['itemtype']."'
                                 AND `items_id` IN (SELECT `id` FROM `$itemtable`
                                 WHERE `entities_id`=$entID AND `is_recursive`=1)";
                     $DB->query($query3) or die("0.78 update entities_id and is_recursive=1
                           in glpi_infocoms for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());
                  } else {
                     $query3="UPDATE `glpi_infocoms`
                              SET `entities_id`=$entID
                              WHERE `itemtype`='".$data['itemtype']."'
                                 AND `items_id` IN (SELECT `id` FROM `$itemtable`
                                 WHERE `entities_id`=$entID)";
                     $DB->query($query3) or die("0.78 update entities_id in glpi_infocoms
                           for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());

                  }

               }
            }
         }
      }
   }

   // Migrate consumable and cartridge and computerdisks entity information
   $items=array('glpi_cartridges' => 'glpi_cartridgeitems',
               'glpi_consumables'=> 'glpi_consumableitems',
               'glpi_computerdisks'=> 'glpi_computers');
   foreach ($items as $linkitem => $sourceitem) {
      if (!FieldExists($linkitem,'entities_id')) {
         displayMigrationMessage("078", $LANG['update'][141].' - '.$linkitem); // Updating schema

         $query = "ALTER TABLE `$linkitem` ADD `entities_id` int(11) NOT NULL DEFAULT 0 AFTER `id`,
                           ADD INDEX `entities_id` ( `entities_id` )";
         $DB->query($query) or die("0.78 add entities_id in $linkitem ". $LANG['update'][90] . $DB->error());


         $entities=getAllDatasFromTable('glpi_entities');
         $entities[0]="Root";

         foreach ($entities as $entID => $val) {
            $query3="UPDATE $linkitem
                     SET `entities_id`='$entID'
                     WHERE ".getForeignKeyFieldForTable($sourceitem)." IN
                        (SELECT `id` FROM $sourceitem WHERE `entities_id`='$entID' )";
            $DB->query($query3) or die("0.78 update entities_id in $linkitem ". $LANG['update'][90] . $DB->error());
         }
      }
   }

   // Migrate softwareversions entity information
   if (!FieldExists('glpi_softwareversions','entities_id')) {
      displayMigrationMessage("078", $LANG['update'][141].' - glpi_softwareversions'); // Updating schema

      $query = "ALTER TABLE `glpi_softwareversions` ADD `entities_id` int(11) NOT NULL DEFAULT 0 AFTER `id`,
                        ADD INDEX `entities_id` ( `entities_id` ), ADD `is_recursive` tinyint(1) NOT NULL DEFAULT 0 AFTER `entities_id`";
      $DB->query($query) or die("0.78 add entities_id in glpi_softwareversion ". $LANG['update'][90] . $DB->error());


      $entities=getAllDatasFromTable('glpi_entities');
      $entities[0]="Root";

      foreach ($entities as $entID => $val) {
         // Non recursive ones
         $query3="UPDATE `glpi_softwareversions`
                  SET `entities_id`=$entID, `is_recursive`=0
                  WHERE `softwares_id` IN (SELECT `id` FROM `glpi_softwares`
                     WHERE `entities_id`=$entID AND `is_recursive`=0)";
         $DB->query($query3) or die("0.78 update entities_id and is_recursive=0
               in glpi_softwareversions for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());

         // Recursive ones
         $query3="UPDATE `glpi_softwareversions`
                  SET `entities_id`=$entID, `is_recursive`=1
                  WHERE `softwares_id` IN (SELECT `id` FROM `glpi_softwares`
                     WHERE `entities_id`=$entID AND `is_recursive`=1)";
         $DB->query($query3) or die("0.78 update entities_id and is_recursive=1
               in glpi_softwareversions for ".$data['itemtype']." ". $LANG['update'][90] . $DB->error());

      }
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_mailcollectors'); // Updating schema

   if (!FieldExists("glpi_mailcollectors", "is_active")) {
      $query = "ALTER TABLE `glpi_mailcollectors` ADD `is_active` tinyint( 1 ) NOT NULL DEFAULT '1' ;";
      $DB->query($query) or die("0.78 add is_active in glpi_mailcollectors " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_mailcollectors','date_mod')) {
      $query = "ALTER TABLE `glpi_mailcollectors`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_mailcollectors" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_mailcollectors','comment')) {
      $query = "ALTER TABLE `glpi_mailcollectors`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_mailcollectors" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','rule_mailcollector')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `rule_mailcollector` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.78 add rule_mailcollector to glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
      $query = "UPDATE `glpi_profiles` SET `rule_mailcollector`=`rule_ticket`";
      $DB->query($query) or die("0.78 set default rule_mailcollector same as rule_ticket " . $LANG['update'][90] . $DB->error());
   }
   // Change search pref : add active / date_mod
   $ADDTODISPLAYPREF['MailCollector']=array(2,19);



   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_authldaps'); // Updating schema

   if (!FieldExists('glpi_authldaps','date_mod')) {
      $query = "ALTER TABLE `glpi_authldaps`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_authldaps" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_authldaps','comment')) {
      $query = "ALTER TABLE `glpi_authldaps`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_authldaps" .
                                 $LANG['update'][90] . $DB->error());
   }

   // Change search pref : host, date_mod
   $ADDTODISPLAYPREF['AuthLDAP']=array(3,19);


   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_authldaps'); // Updating schema

   if (!FieldExists('glpi_authmails','date_mod')) {
      $query = "ALTER TABLE `glpi_authmails`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_authmails" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_authmails','comment')) {
      $query = "ALTER TABLE `glpi_authmails`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_authmails" .
                                 $LANG['update'][90] . $DB->error());
   }

   // Change search pref : host, date_mod
   $ADDTODISPLAYPREF['AuthMail']=array(3,19);

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_ocsservers'); // Updating schema

   if (!FieldExists('glpi_ocsservers','date_mod')) {
      $query = "ALTER TABLE `glpi_ocsservers`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_ocsservers" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ocsservers','comment')) {
      $query = "ALTER TABLE `glpi_ocsservers`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_ocsservers" .
                                 $LANG['update'][90] . $DB->error());
   }
   // Change search pref : date_mod / host
   $ADDTODISPLAYPREF['OcsServer']=array(3,19);


   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_profiles'); // Updating schema

   if (!FieldExists('glpi_profiles','date_mod')) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','comment')) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }
   // Change search pref : date_mod / host
   $ADDTODISPLAYPREF['Profile']=array(2,3,19);


   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_printers'); // Updating schema

   if (!FieldExists('glpi_printers','have_ethernet')) {
      $query = "ALTER TABLE `glpi_printers` ADD `have_ethernet` TINYINT( 1 ) NOT NULL
                  DEFAULT '0' AFTER `have_usb`;";

      $DB->query($query) or die("0.78 add have_ethernet to glpi_printers" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_printers','have_wifi')) {
      $query = "ALTER TABLE `glpi_printers` ADD `have_wifi` TINYINT( 1 ) NOT NULL
               DEFAULT '0' AFTER `have_usb` ;";

      $DB->query($query) or die("0.78 add have_wifi to glpi_printers" .
                                 $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][141] . ' - glpi_profiles'); // Updating schema

   if (!FieldExists('glpi_transfers','date_mod')) {
      $query = "ALTER TABLE `glpi_transfers`
                ADD `date_mod` DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";

      $DB->query($query) or die("0.78 add date_mod to glpi_transfers" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_transfers','comment')) {
      $query = "ALTER TABLE `glpi_transfers`
                ADD `comment` text collate utf8_unicode_ci";

      $DB->query($query) or die("0.78 add comment to glpi_transfers" .
                                 $LANG['update'][90] . $DB->error());
   }
   // Change search pref : date_mod
   $ADDTODISPLAYPREF['Transfer']=array(19);

   // Convert events
   displayMigrationMessage("078", $LANG['update'][142] . ' - glpi_events');

   $convert_types=array('tracking'=>'ticket');
   //$convert_service=array('tracking'=>'ticket');
   foreach ($convert_types as $from =>$to) {
      $query2="UPDATE glpi_events SET type='$to' WHERE type='$from';";
      $DB->query($query2) or die("0.78 update events data " . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][142] . ' - ticket bookmarks');

   $query="SELECT * FROM `glpi_bookmarks`
            WHERE `itemtype`='Ticket' AND `type`='".BOOKMARK_SEARCH."';";
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $num=0;
            $num2=0;
            $options = array();
            parse_str($data["query"],$options);
            $newoptions=array();

            foreach ($options as $key => $val) {
               switch ($key){
                  case "status":
                     $newoptions['field'][$num]       = 12;
                     $newoptions['searchtype'][$num]  = 'equals';
                     $newoptions['link'][$num]        = 'AND';
                     if ($val=='old_done' || $val=='old_notdone') {
                        $newoptions['contains'][$num] = 'closed';
                     } else {
                        $newoptions['contains'][$num] = $val;
                     }
                     $num++;
                     break;
                  case "priority":
                     if ($val!=0) {
                        $newoptions['field'][$num]       = 3;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "category":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 7;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "request_type":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 9;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "type":
                     if ($val>0 && isset($data['item']) && $data['item']>0)  {
                        $newoptions['itemtype2'][$num2]  = $typetoname[$val];
                        $newoptions['field2'][$num2]      = 1;
                        $newoptions['searchtype2'][$num2] = 'equals';
                        $newoptions['contains2'][$num2]   = $data['item'];
                        $newoptions['link2'][$num2]        = 'AND';
                        $num2++;
                     }
                     break;
                  case "author":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 4;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "group":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 71;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "assign":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 5;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "assign_group":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 8;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "assign_ent":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 6;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "recipient":
                     if ($val>0) {
                        $newoptions['field'][$num]       = 22;
                        $newoptions['searchtype'][$num]  = 'equals';
                        $newoptions['contains'][$num]    = $val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "date1": // begin from
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 15;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&gt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "date2": // begin to
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 15;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&lt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "enddate1": // end from
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 16;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&gt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "enddate2": // end to
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 16;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&lt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "datemod1": // mod from
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 19;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&gt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "datemod2": // mod to
                     if (strlen($val)>0 && $val!='NULL') {
                        $newoptions['field'][$num]       = 19;
                        $newoptions['searchtype'][$num]  = 'contains';
                        $newoptions['contains'][$num]    = '&lt;='.$val;
                        $newoptions['link'][$num]        = 'AND';
                        $num++;
                     }
                     break;
                  case "tosearch":
                     if (isset($data['search'])) {
                        $search=trim($data['search']);
                        if (strlen($search)>0){
                           $first=false;
                           if (strstr($data['search'],'name')) {
                              $newoptions['field'][$num]       = 1;
                              $newoptions['searchtype'][$num]  = 'contains';
                              $newoptions['contains'][$num]    = $val;
                              $newoptions['link'][$num]        = ($first?'AND':'OR');
                              $first=false;
                              $num++;
                           }
                           if (strstr($data['search'],'contents')) {
                              $newoptions['field'][$num]       = 21;
                              $newoptions['searchtype'][$num]  = 'contains';
                              $newoptions['contains'][$num]    = $val;
                              $newoptions['link'][$num]        = ($first?'AND':'OR');
                              $first=false;
                              $num++;
                           }
                           if (strstr($data['search'],'followup')) {
                              $newoptions['field'][$num]       = 25;
                              $newoptions['searchtype'][$num]  = 'contains';
                              $newoptions['contains'][$num]    = $val;
                              $newoptions['link'][$num]        = ($first?'AND':'OR');
                              $first=false;
                              $num++;
                           }
                           if (strstr($data['search'],'ID')) {
                              $newoptions['field'][$num]       = 2;
                              $newoptions['searchtype'][$num]  = 'contains';
                              $newoptions['contains'][$num]    = $val;
                              $newoptions['link'][$num]        = 'AND';
                              $first=false;
                              $num++;
                           }
                        }
                     }
                     break;
               }
            }
            if ($num>0 || $num2 >0) {
               $newoptions['glpisearchcount']=$num;
               $newoptions['glpisearchcount2']=$num2;
               $newoptions['itemtype']='Ticket';
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(append_params($newoptions))."' WHERE id=".$data['id'].";";
               $DB->query($query2) or die("0.78 update ticket bookmarks " . $LANG['update'][90] . $DB->error());

            } else {
               $query2="DELETE FROM glpi_bookmarks WHERE id=".$data['id'].";";
               $DB->query($query2) or die("0.78 delete ticket bookmarks : cannot convert " . $LANG['update'][90] . $DB->error());
            }
            // Lost paramaters
            //only_computers=1&contains=dddd&field=moboard.designation&
         }
      }
   }


   if (!TableExists('glpi_ticketvalidations')) {
      $query = "CREATE TABLE `glpi_ticketvalidations` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `entities_id` int(11) NOT NULL default '0',
                  `users_id` int(11) NOT NULL default '0',
                  `tickets_id` int(11) NOT NULL default '0',
                  `users_id_validate` int(11) NOT NULL default '0',
                  `comment_submission` text collate utf8_unicode_ci,
                  `comment_validation` text collate utf8_unicode_ci,
                  `status` varchar(255) collate utf8_unicode_ci default 'waiting',
                  `submission_date` datetime default NULL,
                  `validation_date` datetime default NULL,
                  `is_deleted` tinyint(1) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_deleted` (`is_deleted`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.78 create glpi_ticketvalidations " . $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['TicketValidation']=array(3,2,8,4,9,7);
   }

   if (!FieldExists('glpi_profiles','validate_ticket')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `validate_ticket` char(1) collate utf8_unicode_ci default NULL";
      $DB->query($query) or die("0.78 add validate_ticket to glpi_profiles " . $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `validate_ticket`='1' WHERE `interface` = 'central' ";
		$DB->query($query) or die("0.78 add validate_ticket write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_profiles` ADD `create_validation` char(1) collate utf8_unicode_ci default NULL";
      $DB->query($query) or die("0.78 add create_validation to glpi_profiles " . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `create_validation`=`own_ticket`";
		$DB->query($query) or die("0.78 add create_validation right if can own ticket" . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists('glpi_mailcollectors','entities_id')) {
      $ranking = 0;
      foreach (getAllDatasFromTable('glpi_mailcollectors') as $collector) {
         $query = "INSERT INTO `glpi_rules` VALUES(NULL, -1, 'RuleMailCollector', $ranking,
                                                   '".$collector['name']."',
                                                   '', 'AND', 1, NULL, NULL);";
         $DB->query($query) or die("0.78 error inserting new maigate rule".
                                    $collector['name']." " . $LANG['update'][90] . $DB->error());
         $query = "SELECT `id`
                   FROM `glpi_rules`
                   WHERE `sub_type`='RuleMailCollector' AND `ranking`=$ranking";
         $result = $DB->query($query) or die("0.78 error getting new maigate rule".
                                    $collector['name']." " . $LANG['update'][90] . $DB->error());

         if ($DB->numrows($result) > 0) {
            $newID = $DB->result($result,0,'id');
            $query = "INSERT INTO `glpi_rulecriterias` VALUES(NULL, $newID, 'mailcollector', 0,
                                                              '".$collector['id']."');";
            $DB->query($query)or die("0.78 error getting new criteria for rule".
                                    $collector['name']." " . $LANG['update'][90] . $DB->error());
            $query = "INSERT INTO `glpi_ruleactions` VALUES(NULL, $newID, 'assign',
                                                            'entities_id',
                                                            '".$collector['entities_id']."');";
            $DB->query($query) or die("0.78 error getting new action for rule".
                                    $collector['name']." " . $LANG['update'][90] . $DB->error());
         }

         $ranking++;
      }

      if (!TableExists('glpi_notimportedemails')) {
         $query = "CREATE TABLE `glpi_notimportedemails` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `from` varchar(255) NOT NULL,
                    `to` varchar(255) NOT NULL,
                    `mailcollectors_id` int(11) NOT NULL DEFAULT '0',
                    `date` datetime NOT NULL,
                    `subject` text,
                    `messageid` varchar(255) NOT NULL,
                    `reason` int(11) NOT NULL DEFAULT '0',
                    `users_id` int(11) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";
         $DB->query($query) or die("0.78 add table glpi_notimportedemails".
                                   $LANG['update'][90] . $DB->error());
         $ADDTODISPLAYPREF['NotImportedEmail']=array(2,5,4,6,16,19);
      }

   if (!FieldExists("glpi_profiles","entity_rule_ticket")) {
      $query = "ALTER TABLE `glpi_profiles` ADD `entity_rule_ticket` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.78 add entity_rule_ldap in glpi_profiles" . $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `entity_rule_ticket`='w' WHERE `name` IN ('admin', 'superadmin')";
      $DB->query($query) or die("0.78 update default entity_rule_ticket rights" . $LANG['update'][90] . $DB->error());

   }
      $query = "ALTER TABLE `glpi_mailcollectors` DROP INDEX `entities_id` ";
      $DB->query($query) or die("0.78 drop index entities_id from glpi_mailcollectors".
                                   $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_mailcollectors` DROP `entities_id` ";
      $DB->query($query) or die("0.78 drop entities_id from glpi_mailcollectors".
                                   $LANG['update'][90] . $DB->error());

      $query = "DELETE FROM `glpi_displaypreferences` WHERE `itemtype`='MailCollector' AND `num`='80'";
      $DB->query($query) or die("0.78 drop entities_id from collector's display preferences'".
                                   $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("078", $LANG['update'][142] . ' - glpi_displaypreferences');

   // Add search values for tickets
   $ADDTODISPLAYPREF['Ticket']=array(12,19,15,3,4,5,7);


   foreach ($ADDTODISPLAYPREF as $type => $tab) {

      $query="SELECT DISTINCT users_id FROM glpi_displaypreferences WHERE itemtype='$type';";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query="SELECT max(rank) FROM glpi_displaypreferences
                           WHERE users_id='".$data['users_id']."' AND `itemtype`='$type';";
               $result=$DB->query($query);
               $rank=$DB->result($result,0,0);
               $rank++;
               foreach ($tab as $newval) {
                  $query="SELECT * FROM glpi_displaypreferences
                           WHERE users_id='".$data['users_id']."' AND num=$newval AND itemtype='$type';";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query="INSERT INTO glpi_displaypreferences (`itemtype` ,`num` ,`rank` ,`users_id`)
                                 VALUES ('$type', '$newval', '".$rank++."', '".$data['users_id']."');";
                        $DB->query($query);
                     }
                  }
               }
            }
         } else { // Add for default user
            $rank=1;
            foreach ($tab as $newval) {
               $query="INSERT INTO glpi_displaypreferences (`itemtype` ,`num` ,`rank` ,`users_id`)
                        VALUES ('$type', '$newval', '".$rank++."', '0');";
               $DB->query($query);
            }
         }
      }
   }

   if (!FieldExists('glpi_authldaps','is_default')) {
      $query = "ALTER TABLE `glpi_authldaps` ADD `is_default` TINYINT( 1 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 add is_default to glpi_authldaps " . $LANG['update'][90] . $DB->error());

      $query = "SELECT count(*) as cpt FROM `glpi_authldaps`";
      $result = $DB->query($query);
      $number_servers = $DB->result($result,0,'cpt');

      if ($number_servers >= 1) {
         //If only one server defined
         if ($number_servers==1) {
            $query = "SELECT `id` FROM `glpi_authldaps`";
            $result = $DB->query($query);
            $ldapservers_id = $DB->result($result,0,'id');
         }
         //If more than one server defined, get the most used
         else {
            $query = "SELECT `auths_id`, count(auths_id) as cpt
                FROM `glpi_users`
                WHERE `authtype` = '3'
                GROUP BY `auths_id`
                ORDER BY `cpt` DESC";
            $result = $DB->query($query);
            $ldapservers_id= $DB->result($result,0,'auths_id');
         }
         $query = "UPDATE `glpi_authldaps` SET `is_default`='1' WHERE `id`='".$ldapservers_id."'";
         $DB->query($query) or die("0.78 set default directory " . $LANG['update'][90] . $DB->error());
      }

   }

   if (!FieldExists('glpi_entitydatas','autoclose_delay')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `autoclose_delay` int(11) NOT NULL default '0'";
      $DB->query($query) or die("0.78 add autoclose_delay to glpi_entitydatas " . $LANG['update'][90] . $DB->error());
   }


   if (TableExists('glpi_ruleldapparameters')) {
      $query = "RENAME TABLE `glpi_ruleldapparameters`
                   TO `glpi_rulerightparameters` ;";
      $DB->query($query) or die("0.78 rename glpi_ruleldapparameters to glpi_rulerightparameters".
                                   $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_rulerightparameters` ADD `comment` TEXT NOT NULL ";
      $DB->query($query) or die("0.78 add comment to glpi_rulerightparameters".
                                   $LANG['update'][90] . $DB->error());

   }

   if (!FieldExists('glpi_rules','is_recursive')) {
      $query = "ALTER TABLE `glpi_rules` ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.78 add is_recursive to glpi_rules".
                                   $LANG['update'][90] . $DB->error());
      $query = "UPDATE `glpi_rules` SET `entities_id`='0' WHERE `entities_id`='-1'";
      $DB->query($query) or die("0.78 set entities_id to 0 where value is -1 in glpi_rules".
                                   $LANG['update'][90] . $DB->error());
      $query = "UPDATE `glpi_rules` SET `is_recursive`='1' WHERE `sub_type`='RuleTicket'";
      $DB->query($query) or die("0.78 set is_recursive to 1 for RuleTicket in glpi_rules".
                                   $LANG['update'][90] . $DB->error());
   }
   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("078"); // End

   return $updateresult;
}
?>
