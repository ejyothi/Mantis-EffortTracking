<?php
/*
   Copyright 2011 Michael L. Baker

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

   Notes: Based on the Time Tracking plugin by Elmar:
   2005 by Elmar Schumacher - GAMBIT Consulting GmbH
   http://www.mantisbt.org/forums/viewtopic.php?f=4&t=589
*/
class EffortTrackingPlugin extends MantisPlugin {

	function register() {
		$this->name = 'Effort Tracking';
		$this->description = 'Effort Tracking plugin to record effort estimation per issue and track hours spent by developers. Based on the work done by Michael L. Baker for Time Tracking Plugin';
		$this->page = 'config_page';

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0'
		);

		$this->author = 'Manilal K M';
		$this->contact = 'manilal@ejyothi.com';
		$this->url = 'https://github.com/ejyothi/Mantis-EffortTracking';
	}

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_effort',
			'EVENT_MENU_ISSUE'     => 'timerecord_menu',
			'EVENT_MENU_MAIN'      => 'showreport_menu',
		);
	}

	function config() {
		return array(
			'admin_own_threshold'   => DEVELOPER,
			'view_others_threshold' => MANAGER,
			'admin_threshold'       => ADMINISTRATOR
		);
	}

	function init() {
		$t_path = config_get_global('plugin_path' ). plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		set_include_path(get_include_path() . PATH_SEPARATOR . $t_path);
		require_once( 'et_html_api.php' );
		require_once( 'core_api.php' );
	}


	/**
	 * Show EffortTracking information when viewing bugs.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function view_bug_effort( $p_event, $p_bug_id ) 
	{
		view_effort_plan_details( $p_bug_id );
		view_effort_spent_details( $p_bug_id );

	} # function end

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'effortspent' ), "
				id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				bug_id             I       DEFAULT NULL UNSIGNED,
				user_id            I       DEFAULT NULL UNSIGNED,
				factor_id          I       DEFAULT NULL UNSIGNED,
				date_worked		   T       DEFAULT NULL,
				hours_worked	   F(15,3) DEFAULT NULL,
				timestamp          T       DEFAULT NULL
				" )
			),
			array( 'CreateTableSQL', array( plugin_table( 'factors' ), "
                id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                project_id         I       DEFAULT NULL UNSIGNED,
                factor_name        C(255)  DEFAULT NULL
                " )
            ),
			array( 'CreateTableSQL', array( plugin_table( 'effortplan' ), "
                id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                bug_id         I       DEFAULT NULL UNSIGNED,
                factor_id          I       DEFAULT NULL UNSIGNED,
				user_id            I       DEFAULT NULL UNSIGNED,
				planned_hours      F(15,3) DEFAULT NULL,
				timestamp          T       DEFAULT NULL
                " )
            ),
			array( 'InsertData', array( plugin_table( 'factors' ), "
				(project_id, factor_name) VALUES
				('0', 'Requirements'),
				('0', 'Design'),
				('0', 'User Interface'),
				('0', 'Implementation'),
				('0', 'Unit Testing'),
				('0', 'System Testing')
                " )
            ),
		);
	}

	function timerecord_menu() {
		$bugid =  gpc_get_int( 'id' );
		if( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $bugid )
		 || access_has_bug_level( plugin_config_get( 'view_others_threshold' ), $bugid ) ) {
			$import_page = 'view.php?';
			$import_page .= 'id=';
			$import_page .= $bugid ;
			$import_page .= '#timerecord';

			return array( plugin_lang_get( 'timerecord_menu' ) => $import_page);
		}
		else {
			return array ();
		}
	}

	function showreport_menu() {
		if ( access_has_global_level( plugin_config_get( 'admin_own_threshold' ) ) ){
			return array( '<a href="' . plugin_page( 'show_report' ) . '">' . plugin_lang_get( 'title' ) . '</a>', );
		}
		else {
			return array ('');
		}
	}


} # class end
?>
