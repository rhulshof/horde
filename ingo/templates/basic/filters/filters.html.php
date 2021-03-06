<?php
if ($this->deleteallowed) {
    $del_img = $this->hordeImage('delete.png', _("Delete"));
}
if ($this->editallowed) {
    $copy_img = $this->hordeImage('copy.png', _("Copy"));
    $nav_down = $this->hordeImage('nav/down.png', _("Move Rule Down"));
    $nav_up = $this->hordeImage('nav/up.png', _("Move Rule Up"));
}
$disable_img = $this->hordeImage('disable.png');
$enable_img = $this->hordeImage('enable.png');
?>
<form method="post" id="filters" name="filters" action="<?php echo $this->formurl ?>">
 <input type="hidden" id="actionID" name="actionID" value="" />

 <div class="header">
  <?php echo _("Existing Rules") ?>
  <?php echo $this->hordeHelp('ingo', 'filters_rules') ?>
 </div>

 <table class="striped">
  <thead>
   <tr class="item">
    <th width="1%"><?php echo _("Edit") ?></th>
    <th class="leftAlign"><?php echo _("Rule") ?></th>
    <th width="1%"><?php echo _("Enabled") ?></th>
<?php if ($this->editallowed): ?>
    <th colspan="3" width="1%"><?php echo _("Move") ?></th>
<?php endif; ?>
   </tr>
  </thead>
  <tbody>
<?php if (!count($this->filter)): ?>
   <tr>
    <td colspan="<?php echo $this->editallowed ? 6 : 3 ?>" class="text">
     <em><?php printf(_("No filters. Click \"%s\" to create a new filter."), _("New Rule")) ?></em>
    </td>
   </tr>
<?php else: ?>
<?php foreach ($this->filter as $v): ?>
   <tr>
    <td class="nowrap">
<?php if ($this->deleteallowed): ?>
<?php if (!empty($v['dellink'])): ?>
     <?php echo $v['dellink'] . $del_img ?></a>
<?php endif; ?>
<?php endif; ?>
<?php if ($this->editallowed): ?>
<?php if (!empty($v['copylink'])): ?>
     <?php echo $v['copylink'] . $copy_img ?></a>
<?php endif; ?>
<?php endif ?>
    </td>
    <td>
     <strong>
      <?php echo $v['number'] ?>.
      <?php if (isset($v['filterimg'])) { echo $this->hordeImage($v['filterimg']); } ?>
      <?php echo $v['descriplink'] ?>
     </strong>
<?php if (!empty($v['enablelink'])): ?>
     <strong>[<?php echo $v['enablelink'] . $disable_img ?><span style="color:red"><?php echo _("disabled - click to enable") ?></span></a>]</strong>
<?php endif; ?>
    </td>
    <td style="text-align:center">
<?php if ($this->editallowed): ?>
<?php if (!empty($v['disablelink'])): ?>
     <?php echo $v['disablelink'] . $enable_img ?></a>
<?php else: ?>
     <?php echo $v['enablelink'] . $disable_img ?></a>
<?php endif; ?>
<?php elseif (!empty($v['disablelink'])): ?>
     <?php echo $enable_img ?>
<?php else: ?>
     <?php echo $disable_img ?>
<?php endif; ?>
    </td>
<?php if ($this->editallowed): ?>
    <td class="nowrap">
<?php if ($v['uplink']): ?>
     <?php echo $v['uplink'] . $nav_up ?></a>
<?php endif ?>
    </td>
    <td class="nowrap">
<?php if ($v['downlink']): ?>
     <?php echo $v['downlink'] . $nav_down ?></a>
<?php endif ?>
    </td>
    <td class="nowrap">
     <label>
      <?php echo _("To:") ?>
      <input type="text" size="2" onchange="IngoFilters.moveFromTo(<?php echo $v['number'] ?>, this.value, '<?php echo $v['upurl'] ?>', '<?php echo $v['downurl'] ?>');"/>
     </label>
    </td>
<?php endif; ?>
   </tr>
<?php endforeach; ?>
<?php endif; ?>
  </tbody>
 </table>

<?php if ($this->canapply): ?>
 <p class="horde-form-buttons">
  <input class="button" id="apply_filters" type="button" value="<?php echo _("Apply Filters") ?>" />
 </p>
<?php endif; ?>
</form>

<?php if ($this->settings): ?>
<br />

<form method="post" name="filtersettings" action="<?php echo $this->formurl ?>">
 <input type="hidden" name="actionID" value="settings_save" />
 <h1 class="header">
  <?php echo _("Additional Settings") ?>
 </h1>

 <div class="horde-content">
  <p>
   <?php echo $this->checkBoxTag('show_filter_msg', 1, $this->show_filter_msg) ?>
   <?php echo $this->hordeLabel('show_filter_msg', _("Display detailed notification when each filter is applied?")) ?>
   <?php echo $this->hordeHelp('ingo', 'pref-show_filter_msg') ?>
  </p>
  <p>
   <?php echo $this->hordeLabel('filter_seen', _("Filter Options")) ?>
   <select id="filter_seen" name="filter_seen">
    <?php echo $this->optionTag(0, _("Filter All Messages"), empty($this->flags)) ?>
    <?php echo $this->optionTag(Ingo::FILTER_UNSEEN, _("Filter Only Unseen Messages"), $this->flags == Ingo::FILTER_UNSEEN) ?>
    <?php echo $this->optionTag(Ingo::FILTER_SEEN, _("Filter Only Seen Messages"), $this->flags == Ingo::FILTER_SEEN) ?>
   </select>
   <?php echo $this->hordeHelp('ingo', 'pref-filter_seen') ?>
  </p>
 </div>
 <p class="horde-form-buttons">
  <input class="button" type="submit" value="<?php echo _("Save Settings") ?>" />
 </p>
</form>
<?php endif; ?>
