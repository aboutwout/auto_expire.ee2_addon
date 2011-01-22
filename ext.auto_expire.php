<?php

/**
* @package ExpressionEngine
* @author Wouter Vervloet
* @copyright  Copyright (c) 2010, Baseworks
* @license    http://creativecommons.org/licenses/by-sa/3.0/
* 
* This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
* To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
* or send a letter to Creative Commons, 171 Second Street, Suite 300,
* San Francisco, California, 94105, USA.
* 
*/

if ( ! defined('EXT')) { exit('Invalid file request'); }

class Auto_expire_ext
{
  public $settings            = array();
  
  public $name                = 'Auto Expire';
  public $version             = 2.3;
  public $description         = "Automatically set an entry's expiration date.";
  public $settings_exist      = 'y';
  public $docs_url            = '';
  
  public $site_id             = 1;
  
  private $_time_diff         = false;
  private $_time_unit         = false;
  private $status             = false;
  
  public $time_units          = array(
                                  1 => 'minutes',
                                  2 => 'hours',
                                  3 => 'days',
                                  4 => 'weeks',
                                  5 => 'months',
                                  6 => 'years'
                                );
			
	// -------------------------------
	// Constructor
	// -------------------------------
	function Auto_expire_ext($settings='')
	{
	  $this->__construct($settings);
	}
	
	function __construct($settings='')
	{
	  
	  /** -------------------------------------
    /**  Get global instance
    /** -------------------------------------*/
    $this->EE =& get_instance();
	  
		$this->settings = $settings;
		
		$this->site_id = $this->EE->config->item('site_id');
    
	}
	// END Auto_expire_ext
	
	
  /**
  * Set the expiration date if needed
  */
  function set_expiration_date($channel_id=0, $autosave=false)
  {
    
    if(!$channel_id || $autosave === true) return;
    
    $expiration_date_in = isset($this->EE->api_channel_entries->data['expiration_date']) ? $this->EE->api_channel_entries->data['expiration_date'] : false;
        
    // channel has auto expire settings set and has no expiration date set
    if ($this->_auto_expire_channel($channel_id) && !$expiration_date_in) {

      $entry_date = new DateTime($this->EE->input->post('entry_date'));
      $expiration_date = clone $entry_date;
      
      $expiration_date->modify('+'.$this->_time_diff.' '.$this->time_units[$this->_time_unit]);
      
      $this->EE->api_channel_entries->data['expiration_date'] = $expiration_date->format('Y-m-d H:i');
      
    }

  }
  // END set_expiration_date
  
  /**
  * Set the expiration date if needed
  */
  function set_expiration_date_saef()
  {
    $channel_id = $this->EE->input->post('channel_id');
    $expiration_date_in = $_POST['expiration_date'];
        
    // channel has auto expire settings set and has no expiration date set
    if ($this->_auto_expire_channel($channel_id) && !$expiration_date_in) {

      $entry_date = new DateTime($this->EE->input->post('entry_date'));
      $expiration_date = clone $entry_date;
      
      $expiration_date->modify('+'.$this->_time_diff.' '.$this->time_units[$this->_time_unit]);
      
      $_POST['expiration_date'] = $expiration_date->format('Y-m-d H:i');
    }

  }
  // END set_expiration_date
  
  
  /**
  * Modifies control panel html by adding the Auto Expire
  * settings panel to Admin > Weblog Administration > Weblog Management > Edit Weblog
  */
  function settings_form($current)
  {
    
    if($this->EE->input->post('time_diff') && $this->EE->input->post('time_unit')) {
      $this->save_settings_form();
      $this->EE->javascript->output('$.ee_notice("Settings saved!", {type : "success", open : true})');
    }
    
    // now we can fetch the language file
//    $this->EE->lang->loadfile('auto_expire');

    $channel_query = $this->EE->db->query("SELECT channel_id, channel_title FROM exp_channels");

    $channels = array();
    
    foreach($channel_query->result() as $row) {
      
      $statuses = $this->EE->db->query("SELECT status_id as id, status as name FROM exp_statuses s NATURAL JOIN exp_status_groups sg NATURAL JOIN exp_channels c WHERE c.channel_id = ".$row->channel_id);
            
      $expire = $this->_fetch_preferences($row->channel_id);
      
      $channels[] = array(
        'id' => $row->channel_id,
        'title' => $row->channel_title,
        'time_diff' => $expire['time_diff'],
        'time_unit' => $expire['time_unit'],
        'status' => $expire['status'],
        'statuses' => $statuses
      );
    }
    
    $vars = array(
      'time_units' => $this->time_units,
      'channels' => $channels
    );
    
    return $this->EE->load->view('settings_form', $vars, TRUE);
   
  }
  // END settings_form

  /**
  * Chekc if there are any expired entries and change the status if needed
  */
  function change_status_expired_entries()
  {
    $query = $this->EE->db->query("SELECT ae.channel_id, ae.status, s.status as status_name FROM exp_auto_expire ae LEFT JOIN exp_statuses s ON ae.status = s.status_id WHERE ae.status != 0");


    if($query->num_rows() == 0) return false;
    
    foreach($query->result() as $row) {      
           
      $data = array(
        'status' => $row->status_name
      );
      
      $sql = $this->EE->db->update_string('exp_channel_titles', $data, "channel_id = '".$row->channel_id."' AND status != '".$row->status_name."' AND expiration_date != '0' AND expiration_date <  ".time());
            
      $this->EE->db->query($sql);
    }            
  }

  /**
  * Saves the auto expire settings.
  */
  function save_settings_form()
  {
    $time_diffs = $this->EE->input->post('time_diff');
    $time_units = $this->EE->input->post('time_unit');
    $which = $this->EE->input->post('which');
    $at_end = $this->EE->input->post('at_end');
    $statuses = $this->EE->input->post('status');    

    echo "<pre>".print_r($_POST, true)."</pre>";
    exit;

    foreach($time_diffs as $channel_id => $value)
    {
      // Default values
      $data = array(
        'channel_id' => $channel_id,
        'time_diff' => 0,
        'time_unit' => 0,
        'status' => 0
      );
       
      // Values have been set
      if(is_numeric($time_diffs[$channel_id]) && $time_diffs[$channel_id] && is_numeric($time_units[$channel_id]) && $time_units[$channel_id])
      {
        $data['time_diff'] = $time_diffs[$channel_id];
        $data['time_unit'] = $time_units[$channel_id];
        $data['status'] = $statuses[$channel_id];
      }
      
      $this->EE->db->query("INSERT INTO exp_auto_expire (channel_id, time_diff, time_unit, status) VALUES (".$channel_id.", ".$data['time_diff'].", ".$data['time_unit'].", ".$data['status'].") ON DUPLICATE KEY UPDATE channel_id=VALUES(channel_id), time_diff=VALUES(time_diff), time_unit=VALUES(time_unit), status=VALUES(status)");
    }
    
    
  }
  // END save_settings_form
  
  
  function _fetch_preferences($channel_id)
  {
    
    if( !$channel_id ) return false;
    
    $return = array(
      'time_diff' => 0,
      'time_unit' => 0,
      'status' => 0
    );
    
    $query = $this->EE->db->query("SELECT time_diff, time_unit, status FROM exp_auto_expire WHERE channel_id = $channel_id");
    
    if($query->num_rows() > 0) {
      
      $return['time_diff'] = $query->row('time_diff');
      $return['time_unit']  = $query->row('time_unit');
      $return['status']  = $query->row('status');
      
    }
    
    return $return;
    
  }
  // END _fetch_preferences
  
  
  /**
   * Checks whether the expiration date should be set for this channel
   *
   * @param   string $channel_id A channel id.
   * @return  boolean True if a channel requires at least one category, false else.
   */  
  function _auto_expire_channel($channel_id)
  {
    
    if( ! $channel_id ) return false;
    
    $query = $this->EE->db->query("SELECT channel_id, time_diff, time_unit, status FROM exp_auto_expire WHERE channel_id = {$channel_id}");
    
    // If no settings have been set for this channel, unset variables and return false
    if($query->num_rows() === 0) {
      $this->_time_diff = false;
      $this->_time_unit = false;

      return false;
    }
        
    $this->_time_diff = $query->row('time_diff');
    $this->_time_unit = $query->row('time_unit');    
    $this->_status = $query->row('status');    
    
    return ! $this->_time_diff || ! $this->_time_unit ? false : true;
    
  }
  // END	_auto_expire_channel	
	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{

    // hooks array
    $hooks = array(
      'entry_submission_start' => 'set_expiration_date',
      'sessions_end' => 'change_status_expired_entries'
    );

    // insert hooks and methods
    foreach ($hooks AS $hook => $method)
    {
      // data to insert
      $data = array(
        'class'		=> get_class($this),
        'method'	=> $method,
        'hook'		=> $hook,
        'priority'	=> 1,
        'version'	=> $this->version,
        'enabled'	=> 'y',
        'settings'	=> ''
      );

      // insert in database
      $this->EE->db->insert('exp_extensions', $data);
    }

    // add extension table
    $sql[] = 'DROP TABLE IF EXISTS `exp_auto_expire`';
    $sql[] = "CREATE TABLE `exp_auto_expire` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `channel_id` INT NOT NULL UNIQUE KEY, `time_diff` INT NOT NULL, `time_unit` INT NOT NULL, `status` INT NOT NULL)";

    // run all sql queries
    foreach ($sql as $query) {
      $this->EE->db->query($query);
    }

    return true;
	}
	// END activate_extension
	 
	 
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		
    if ($current == '' OR $current == $this->version)
    {
      return FALSE;
    }
    
    if($current < $this->version) { }

    // init data array
    $data = array();

    // Add version to data array
    $data['version'] = $this->version;    

    // Update records using data array
    $this->EE->db->where('class', get_class($this));
    $this->EE->db->update('exp_extensions', $data);
  }
  // END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{		
    // Delete records
    $this->EE->db->where('class', get_class($this));
    $this->EE->db->delete('exp_extensions');
  }
  // END disable_extension

	 
}
// END CLASS
?>