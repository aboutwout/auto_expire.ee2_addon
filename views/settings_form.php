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

table.nostyles td:last-child { border-right:none !important; }
table.nostyles tbody tr td {vertical-align:top !important;}
.nostyles div {position:relative;}
.nostyles div label {font-weight:normal;margin-bottom:.5em;display:block;}
.nostyles label input[type=radio] {margin-right:5px;position:relative;top:1px;}
</style>
<script>
$(function() {});
</script>
<?php if(count($channels) == 0) : ?>
<p style="margin-bottom:1.5em">You haven't created any channels yet. Go to the <a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=channel_add';?>">Channel Management</a> and create one first.</p>
<?php else : ?>
  <?=  form_open('C=addons_extensions&M=extension_settings&file=auto_expire', array(), array('file' => 'auto_expire')) ?>
  <table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
    <thead>
      <tr><th style="width:140px;" class="header">Channel</th><th>Settings</th></tr>
    </thead>
  <tbody>
  <?php
    $j = $i = 0;
    foreach($channels as $channel) :
  ?>
    <tr class="<?=($i%2) ? 'even' : 'odd';?>">
      <td class="tableCellOne"><b><?=$channel['title']?></b></td>
      <td class="tableCellOne">
        <table class="nostyles">
          <tr>
            <td style="width:60%;border-right:1px dotted #D0D7DF;">
              <div style="margin-bottom:1em">
                <label><!--<input type="radio" name="which[<?=$channel['id']?>]" value="diff" checked="checked" />--><?=lang('pref_auto_expire')?>...</label>
                <input dir="ltr" style="width:20%;margin-right:5px" type="text" name="time_diff[<?=$channel['id']?>]" id="time_diff" value="<?=$channel['time_diff']?>" size="" maxlength="" class="" tabindex="<?=++$j?>" /> 
                <select name="time_unit[<?=$channel['id']?>]" class="select" style="width:120px" tabindex="<?=++$j?>">
                  <option value="0"><?=lang('select_period')?></option>
            <?php foreach ($time_units as $key => $val) : ?>          
                  <option value="<?=$key?>" <?php if($key == $channel['time_unit']) : ?> selected="selected"<?php endif; ?>><?=lang($val)?></option>
            <?php endforeach; ?>
                </select>
              </div>
<!--              
              <div style="margin-bottom:.5em">
                <label><input type="radio" name="which[<?=$channel['id']?>]" value="end" />expire at the end of...</label>
                <select name="at_end[<?=$channel['id']?>]" class="select" style="width:120px" >
                  <option value=""><?=lang('select_period')?></option>
                  <option value="day">today</option>
                  <option value="week">this week</option>
                  <option value="month">this month</option>
                  <option value="year">this year</option>
                </select>
            </div>
-->
            </td>
            <td style="width:40%;">
              <div style="margin-bottom:.5em"><?=lang('pref_change_status')?></div>
              <select name="status[<?=$channel['id']?>]" class="select" style="width:20em" tabindex="<?=++$j?>">
                <option value="0"><?=lang('pref_dont_change_status')?></option>
          <?php foreach ($channel['statuses']->result() as $status) : ?>          
                <option value="<?=$status->id?>" <?php if($status->id == $channel['status']) : ?> selected="selected"<?php endif; ?>><?=ucfirst($status->name)?></option>
          <?php endforeach; ?>
              </select>
            </td>
          </tr>
        </table>
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