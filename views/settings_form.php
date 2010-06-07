<style>
.nostyles {
  width:100%;
  margin:0 !important;
  border:none;
  table-layout:fixed;
}

.nostyles td {
  padding:0;
  border-left:none !important;
  border-bottom:none !important;
}

.nostyles td:first-child { border-right:1px dotted #D0D7DF; }
table.nostyles td:last-child { border-right:none !important; }

</style>
<?php if(count($channels) == 0) : ?>
<p style="margin-bottom:1.5em">You haven't created any channels yet. Go to the <a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=channel_add';?>">Channel Management</a> and create one first.</p>
<?php else : ?>
  <?=  form_open('C=addons_extensions&M=extension_settings&file=auto_expire', array(), array('file' => 'auto_expire')) ?>
  <table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
    <thead>
      <tr><th style="width:25%;" class="header">Channel</th><th>Settings</th></tr>
    </thead>
  <tbody>
  <?php
    $j = $i = 0;
    foreach($channels as $channel) :
  ?>
    <tr class="<?=($i%2) ? 'even' : 'odd';?>">
      <td class="tableCellOne" style="width:25%;"><b><?=$channel['title']?></b></td>
      <td class="tableCellOne" style="width:75%;">
        <table class="nostyles"><tr><td style="width:50%;">
          <div style="margin-bottom:.5em"><?=lang('pref_auto_expire')?></div>
          <input dir="ltr" style="width:20%;margin-right:5px" type="text" name="time_diff[<?=$channel['id']?>]" id="time_diff" value="<?=$channel['time_diff']?>" size="" maxlength="" class="" tabindex="<?=++$j?>" /> 
          <select name="time_unit[<?=$channel['id']?>]" class="select" style="width:70%" tabindex="<?=++$j?>">
            <option value="0"><?=lang('select_period')?></option>
      <?php foreach ($time_units as $key => $val) : ?>          
            <option value="<?=$key?>" <?php if($key == $channel['time_unit']) : ?> selected="selected"<?php endif; ?>><?=lang($val)?></option>
      <?php endforeach; ?>
          </select>
        </td>
        <td style="width:50%;">
          <div style="margin-bottom:.5em"><?=lang('pref_change_status')?></div>
          <select name="status[<?=$channel['id']?>]" class="select" style="width:20em" tabindex="<?=++$j?>">
            <option value="0"><?=lang('pref_dont_change_status')?></option>
      <?php foreach ($channel['statuses']->result() as $status) : ?>          
            <option value="<?=$status->id?>" <?php if($status->id == $channel['status']) : ?> selected="selected"<?php endif; ?>><?=ucfirst($status->name)?></option>
      <?php endforeach; ?>
          </select>
        </td></tr></table>
      </td>
    </tr>
  <?php
    $i++;
    endforeach;
  ?>
  </tbody>
</table>
<input type="submit" value="Save settings" class="submit" />
<?=  form_close(); ?>
<?php endif; ?>