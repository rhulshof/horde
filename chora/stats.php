<?php
/**
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';

try {
    $fl = $VC->getFileObject($where, array('cache' => $cache));
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$extraLink = Chora::getFileViews();

$stats = array();
foreach ($fl->logs as $lg) {
    $qa = $lg->queryAuthor();
    if (!isset($stats[$qa])) {
        $stats[$qa] = 0;
    }
    ++$stats[$qa];
}
arsort($stats);

$title = sprintf(_("Statistics for %s"), Text::htmlallspaces($where));
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/stats/stats.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
