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
  public $version             = 2.5;
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
    if ( $this->_auto_expire_channel($channel_id) && ! $expiration_date_in )
    {

      $entry_date = new DateTime($this->EE->input->post('entry_date'));
      $expiration_date = clone $entry_date;
      
      $expiration_date->modify('+'.$this->_time_diff.' '.$this->time_units[$this->_time_unit]);
      
      $this->EE->api_channel_entries->data['expiration_date'] = $expiration_date->format('Y-m-d H:i');
      $_POST['expiration_date'] = $expiration_date->format('Y-m-d H:i');
      
    }

  }
  // END set_expiration_date
  
  
  /**
  * Set the expiration date if needed
  */
  function safecracker_submit_entry_start($OBJ=false)
  {
        
    if( ! $OBJ ) return;
       
    $expiration_date_in = $this->EE->input->post('expiration_date') ? $this->EE->input->post('expiration_date') : false;
        
    // channel has auto expire settings set and has no expiration date set
    if ( $this->_auto_expire_channel($OBJ->channel['channel_id']) && ! $expiration_date_in )
    {

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
    $this->settings = $current;
        
    if($this->EE->input->post('time_diff') && $this->EE->input->post('time_unit')) {
      $this->save_settings_form();
    }
        
    $channel_query = $this->EE->db->select('channel_id, channel_title')->where('site_id', $this->site_id)->get('channels');

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
  * Check if there are any expired entries and change the status if needed
  */
  function change_status_expired_entries()
  {
    
    $statuses = array();
    
    $status_query = $this->EE->db->get('statuses'); 
    
    foreach($status_query->result() as $row)
    {
      $statuses[$row->status_id] = $row->status;
    }

    if( ! isset($this->settings[$this->site_id]) ) return false;

    foreach( $this->settings[$this->site_id] as $channel => $prefs )
    {

      if( ! isset($prefs['status']) || $prefs['status'] == 0 ) continue; 

      $status = $statuses[$prefs['status']];

      $data = array(
        'status' => $status
      );
      
      $this->EE->db->where("channel_id = '".$channel."' AND status != '".$status."' AND expiration_date != '0' AND expiration_date < ".time())->update('channel_titles', $data);
    }
    
  }

  /**
  * Saves the auto expire settings.
  */
  function save_settings_form()
  {
    
    $allowed_prefs = array('which', 'time_diff', 'time_unit', 'at_end', 'status');

    foreach($allowed_prefs as $key => $pref)
    {
      
      if( ! isset($_POST[$pref]) ) continue;
      
      foreach($_POST[$pref] as $channel => $val)
      {
        
        // If time difference is not numeric, set it to '0'
        if( $pref === 'time_diff' && ! is_numeric($val) )
        {
          $val = 0;
        }
        
        $this->settings[$this->site_id][$channel][$pref] = $val;
        
      }
      
    }
    
    $data = array(
      'settings' => serialize($this->settings)
    );
    
    // Update the settings
    $this->EE->db->where('class', get_class($this))->update('extensions', $data);
    
    $this->EE->javascript->output('$.ee_notice("Settings saved!", {type : "success"})');
    
  }
  // END save_settings_form
  
  
  function _fetch_preferences($channel_id)
  {

    if( !$channel_id ) return false;
    
    $prefs = array(
      'time_diff' => 0,
      'time_unit' => 0,
      'status' => 0,
      'end_at' => 0,      
      'which' => ''

    );
            
    if(isset($this->settings[$this->site_id]) && isset($this->settings[$this->site_id][$channel_id]))
    {
      return $this->settings[$this->site_id][$channel_id];
    }

    return $prefs;
    
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
    
    $prefs = $this->_fetch_preferences($channel_id);
    
    if($prefs['time_diff'] === 0) return false;

    $this->_time_diff = $prefs['time_diff'];
    $this->_time_unit = $prefs['time_unit'];    
    $this->_status = $prefs['status'];    

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
      'safecracker_submit_entry_start' => 'safecracker_submit_entry_start',
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

    $this->EE->load->dbforge();
    $this->EE->dbforge->drop_table('auto_expire');

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

    // init data array
    $data = array();
    $settings = array();
    
    if($current < 2.4)
    {
      $db_settings = $this->EE->db->get('auto_expire');
      
      foreach($db_settings->result() as $row)
      {
        $settings[$this->site_id][$row->channel_id] = array(
          'time_diff' => $row->time_diff,
          'time_unit' => $row->time_unit,
          'status' => $row->status,
          'end_at' => 0,          
          'which' => ''                
        );
      }
      
      $this->EE->load->dbforge();
      $this->EE->dbforge->drop_table('auto_expire');

    }

    // Add version to data array
    $data['version'] = $this->version;
    $data['settings'] = serialize($settings);


    // Update records using data array
    $this->EE->db->where('class', get_class($this))->update('extensions', $data);
    
    return TRUE;
    
  }
  // END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{	
    $this->EE->load->dbforge();
    $this->EE->dbforge->drop_table('auto_expire');
	  
    // Delete records
    $this->EE->db->where('class', get_class($this));
    $this->EE->db->delete('extensions');
  }
  // END disable_extension

	 
}
// END CLASS
?>