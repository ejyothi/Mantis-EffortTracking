<?php
/*
*/

function print_et_factor_options($p_bug_id, $p_select=0)
{
	
	$t_factors = list_factors($p_bug_id);
	foreach($t_factors as $t_factor)
	{
		if( ($p_select != 0) && ($p_select == $t_factor['id']) )
        {
            print "<option value={$t_factor['id']} selected>{$t_factor['factor_name']}</option>";
        }
        else
        {
            print "<option value={$t_factor['id']}>{$t_factor['factor_name']}</option>";
        }
	}
}


	function view_effort_plan_details( $p_bug_id ) 
	{
		$table = plugin_table('effortspent');
		$t_factor_table = plugin_table('factors');
		$t_user_id = auth_get_current_user_id();

		# Pull all Time-Record entries for the current Bug
		if( access_has_bug_level( plugin_config_get( 'view_others_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT e.*,f.factor_name FROM $table as e  LEFT JOIN $t_factor_table AS f ON f.id = e.factor_id WHERE bug_id = $p_bug_id ORDER BY timestamp DESC";
		} else if( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT e.*,f.factor_name FROM $table as e  LEFT JOIN $t_factor_table AS f ON f.id = e.factor_id WHERE bug_id = $p_bug_id AND user_id = $t_user_id ORDER BY timestamp DESC";
		} else {
			// User has no access
			return;
		}

		$result_pull_timerecords = db_query( $query_pull_timerecords );
		$num_timerecords = db_num_rows( $result_pull_timerecords );

		# Get Sum for this bug
		$query_pull_hours = "SELECT SUM(hours_worked) as hours FROM $table WHERE bug_id = $p_bug_id";
		$result_pull_hours = db_query( $query_pull_hours );
		$row_pull_hours = db_fetch_array( $result_pull_hours );

		$t_factors = list_factors($p_bug_id);
		$t_num_factors = count($t_factors);

?>


   <a name="effortplan" id="effortplan" /><br />

<?php
		collapse_open( 'effortplan' );
?>
   <table class="width100" cellspacing="1">
   <tr>
      <td colspan="6" class="form-title">
<?php
		collapse_icon( 'effortplan' );
		echo plugin_lang_get( 'effortplan_title' );
?>
      </td>
   </tr>
   <tr class="row-category">
      <td><div align="center"><?php echo plugin_lang_get( 'user' ); ?></div></td>
	<?php 
		foreach($t_factors as $t_factor) {
			echo "<td><div align=\"center\">{$t_factor['factor_name']}</div></td>";
		} 
	?>
      <td>&nbsp;</td>
   </tr>


<?php
		if ( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$current_date = explode("-", date("Y-m-d"));
?>


   <form name="time_tracking" method="post" action="<?php echo plugin_page('add_record') ?>" >
      <?php echo form_security_field( 'plugin_EffortTracking_add_record' ) ?>

      <input type="hidden" name="bug_id" value="<?php echo $p_bug_id; ?>">

   <tr <?php echo helper_alternate_class() ?>>
     <td><?php echo user_get_name( auth_get_current_user_id() ) ?></td>

		<?php
		foreach($t_factors as $t_factor) 
		{
			echo "<td><div align=\"right\"><input type=\"text\" name=\"time_value[{$t_factor['id']}]\" value=\"00:00\" size=\"5\"></div></td>";
		}
		?>
     <td><input name="<?php echo plugin_lang_get( 'submit' ) ?>" type="submit" value="<?php echo plugin_lang_get( 'submit' ) ?>"></td>
   </tr>
</form>

<?php
		} # END Access Control

		for ( $i=0; $i < $num_timerecords; $i++ ) {
			$row = db_fetch_array( $result_pull_timerecords );
?>


   <tr <?php echo helper_alternate_class() ?>>
      <td><?php echo user_get_name($row["user_id"]); ?></td>
      <td><div align="center"><?php echo date( config_get("short_date_format"), strtotime($row["date_worked"])); ?> </div></td>
      <td><div align="right"><?php echo db_minutes_to_hhmm($row["hours_worked"] * 60) ?> </div></td>
      <td><div align="center"><?php echo string_display_links($row["factor_name"]); ?></div></td>
      <td><div align="center"><?php echo date( config_get("complete_date_format"), strtotime($row["timestamp"])); ?> </div></td>

<?php
			$user = auth_get_current_user_id();
			if( ($user == $row["user"] && access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id) )
			 || access_has_bug_level( plugin_config_get( 'admin_threshold' ), $p_bug_id) ) {
?>


      <td><a href="<?php echo plugin_page('delete_record') ?>&bug_id=<?php echo $p_bug_id; ?>&delete_id=<?php echo $row["id"]; ?><?php echo form_security_param( 'plugin_EffortTracking_delete_record' ) ?>"><?php echo plugin_lang_get( 'delete' ) ?>
</a></td>

<?php
			}
			else {
?>
      <td>&nbsp;</td>

<?php
			}
?>
   </tr>


<?php
		} # End for loop
?>


   <tr class="row-category">
      <td><?php echo plugin_lang_get( 'sum' ) ?></td>
      <td>&nbsp;</td>
      <td><div align="right"><b><?php echo db_minutes_to_hhmm($row_pull_hours['hours']* 60); ?></b></div></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
   </tr>
</table>

<?php
		collapse_closed( 'effortplan' );
?>

<table class="width100" cellspacing="1">
<tr>
   <td class="form-title" colspan="2">
          <?php collapse_icon( 'effortplan' ); ?>
          <?php echo plugin_lang_get( 'effortplan_title' ); ?>
	</td>
</tr>
</table>

<?php
		collapse_end( 'effortplan' );

} # function end

	function view_effort_spent_details( $p_bug_id ) 
	{
		$table = plugin_table('effortspent');
		$t_factor_table = plugin_table('factors');
		$t_user_id = auth_get_current_user_id();

		# Pull all Time-Record entries for the current Bug
		if( access_has_bug_level( plugin_config_get( 'view_others_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT e.*,f.factor_name FROM $table as e  LEFT JOIN $t_factor_table AS f ON f.id = e.factor_id WHERE bug_id = $p_bug_id ORDER BY timestamp DESC";
		} else if( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT e.*,f.factor_name FROM $table as e  LEFT JOIN $t_factor_table AS f ON f.id = e.factor_id WHERE bug_id = $p_bug_id AND user_id = $t_user_id ORDER BY timestamp DESC";
		} else {
			// User has no access
			return;
		}

		$result_pull_timerecords = db_query( $query_pull_timerecords );
		$num_timerecords = db_num_rows( $result_pull_timerecords );

		# Get Sum for this bug
		$query_pull_hours = "SELECT SUM(hours_worked) as hours FROM $table WHERE bug_id = $p_bug_id";
		$result_pull_hours = db_query( $query_pull_hours );
		$row_pull_hours = db_fetch_array( $result_pull_hours );

?>


   <a name="timerecord" id="timerecord" /><br />

<?php
		collapse_open( 'timerecord' );
?>
   <table class="width100" cellspacing="1">
   <tr>
      <td colspan="6" class="form-title">
<?php
		collapse_icon( 'timerecord' );
		echo plugin_lang_get( 'title' );
?>
      </td>
   </tr>
   <tr class="row-category">
      <td><div align="center"><?php echo plugin_lang_get( 'user' ); ?></div></td>
		
      <td><div align="center"><?php echo plugin_lang_get( 'date_worked' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'factor' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'hours' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'date_modified' ); ?></div></td>
      <td>&nbsp;</td>
   </tr>


<?php
		if ( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$current_date = explode("-", date("Y-m-d"));
?>


   <form name="time_tracking" method="post" action="<?php echo plugin_page('add_record') ?>" >
      <?php echo form_security_field( 'plugin_EffortTracking_add_record' ) ?>

      <input type="hidden" name="bug_id" value="<?php echo $p_bug_id; ?>">

   <tr <?php echo helper_alternate_class() ?>>
     <td><?php echo user_get_name( auth_get_current_user_id() ) ?></td>
     <td nowrap>
<?php		
		print "<input ".helper_get_tab_index()." type=\"text\" id=\"date_worked\" name=\"date_worked\" size=\"20\" maxlength=\"16\" value=\"".date('Y-m-d')."\"/>";
		date_print_calendar(); 
		date_finish_calendar( 'date_worked', 'trigger');
		?>
		<!--
        <div align="center">
           <select tabindex="5" name="day"><?php print_day_option_list( $current_date[2] ) ?></select>
           <select tabindex="6" name="month"><?php print_month_option_list( $current_date[1] ) ?></select>
           <select tabindex="7" name="year"><?php print_year_option_list( $current_date[0] ) ?></select>
        </div>
		-->
     </td>
     <td><div align="center">
		<select name="f_factor_id">
		<option value="0">Select Factor</option>
		<?php print_et_factor_options($p_bug_id) ?>
		</select>
	 </td>
     <td><div align="right"><input type="text" name="time_value" value="00:00" size="5"></div></td>
     <td>&nbsp;</td>
     <td><input name="<?php echo plugin_lang_get( 'submit' ) ?>" type="submit" value="<?php echo plugin_lang_get( 'submit' ) ?>"></td>
   </tr>
</form>

<?php
		} # END Access Control

		for ( $i=0; $i < $num_timerecords; $i++ ) {
			$row = db_fetch_array( $result_pull_timerecords );
?>


   <tr <?php echo helper_alternate_class() ?>>
      <td><?php echo user_get_name($row["user_id"]); ?></td>
      <td><div align="center"><?php echo date( config_get("short_date_format"), strtotime($row["date_worked"])); ?> </div></td>
      <td><div align="center"><?php echo string_display_links($row["factor_name"]); ?></div></td>
      <td><div align="right"><?php echo db_minutes_to_hhmm($row["hours_worked"] * 60) ?> </div></td>
      <td><div align="center"><?php echo date( config_get("complete_date_format"), strtotime($row["timestamp"])); ?> </div></td>

<?php
			$user = auth_get_current_user_id();
			if( ($user == $row["user"] && access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id) )
			 || access_has_bug_level( plugin_config_get( 'admin_threshold' ), $p_bug_id) ) {
?>


      <td><a href="<?php echo plugin_page('delete_record') ?>&bug_id=<?php echo $p_bug_id; ?>&delete_id=<?php echo $row["id"]; ?><?php echo form_security_param( 'plugin_EffortTracking_delete_record' ) ?>"><?php echo plugin_lang_get( 'delete' ) ?>
</a></td>

<?php
			}
			else {
?>
      <td>&nbsp;</td>

<?php
			}
?>
   </tr>


<?php
		} # End for loop
?>


   <tr class="row-category">
      <td><?php echo plugin_lang_get( 'sum' ) ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><div align="right"><b><?php echo db_minutes_to_hhmm($row_pull_hours['hours']* 60); ?></b></div></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
   </tr>
</table>

<?php
		collapse_closed( 'timerecord' );
?>

<table class="width100" cellspacing="1">
<tr>
   <td class="form-title" colspan="2">
          <?php collapse_icon( 'timerecord' ); ?>
          <?php echo plugin_lang_get( 'title' ); ?>
	</td>
</tr>
</table>

<?php
		collapse_end( 'timerecord' );

} # function end
