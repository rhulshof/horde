#!@php_bin@
<?php
/**
 * This script outputs statistics on the current memcache pool.
 *
 * Usage: stats.php [--all] [--flush] [--lookup=key] [--raw] [--summary]
 *
 * By default, shows statistics for all servers.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Memcache
 */

// The base file path of horde.
$horde_base = '/path/to/horde';

require_once $horde_base . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));
$cli = Horde_Cli::singleton();

/* Make sure there's no compression. */
@ob_end_clean();

$c = new Console_Getopt();
$argv = $c->readPHPArgv();
array_shift($argv);
$options = $c->getopt2($argv, '', array('all', 'flush', 'lookup=', 'raw', 'summary'));
if (PEAR::isError($options)) {
    $cli->writeln($cli->red("ERROR: Invalid arguments."));
    exit;
}

$all = $raw = $summary = false;
$memcache = &Horde_Memcache::singleton();

foreach ($options[0] as $val) {
    switch ($val[0]) {
    case '--all':
        $all = true;
        break;

    case '--flush':
        if ($cli->prompt($cli->red('Are you sure you want to flush all data?'), array('y' => 'Yes', 'n' => 'No'), 'n') == 'y') {
            $memcache->flush();
            $cli->writeln($cli->green('Done.'));
        }
        exit;

    case '--lookup':
        $data = $memcache->get($val[1]);
        if (!empty($data)) {
            $cli->writeln(print_r($data, true));
        } else {
            $cli->writeln('[Key not found.]');
        }
        exit;

    case '--raw':
        $raw = true;
        break;

    case '--summary':
        $summary = true;
        break;
    }
}

$stats = $memcache->stats();

if ($raw) {
    $cli->writeln(print_r($stats, true));
} elseif (!$summary) {
    $all = true;
}

if ($all || $summary) {
    if ($summary) {
        $total = array();
        $total_keys = array('bytes', 'limit_maxbytes', 'curr_items', 'total_items', 'get_hits', 'get_misses', 'curr_connections', 'bytes_read', 'bytes_written');
        foreach ($total_keys as $key) {
            $total[$key] = 0;
        }
    }

    $i = $s_count = count($stats);

    foreach ($stats as $key => $val) {
        if ($summary) {
            foreach ($total_keys as $k) {
                $total[$k] += $val[$k];
            }
        }

        if ($all) {
            $cli->writeln($cli->green('Server: ' . $key . ' (Version: ' . $val['version'] . ' - ' . $val['threads'] . ' thread(s))'));
            _outputInfo($val, $cli);
            if (--$i || $summary) {
                $cli->writeln();
            }
        }
    }

    if ($summary) {
        $cli->writeln($cli->green('Memcache pool (' . $s_count . ' server(s))'));
        _outputInfo($total, $cli);
    }
}

function _outputInfo($val, $cli)
{
    $cli->writeln($cli->indent('Size:          ' . sprintf("%0.2f", $val['bytes'] / 1048576) . ' MB (Max: ' . sprintf("%0.2f", ($val['limit_maxbytes']) / 1048576) . ' MB - ' . ((!empty($val['limit_maxbytes']) ? round(($val['bytes'] / $val['limit_maxbytes']) * 100, 1) : 'N/A')) . '% used)'));
    $cli->writeln($cli->indent('Items:         ' . $val['curr_items'] . ' (Total: ' . $val['total_items'] . ')'));
    $cli->writeln($cli->indent('Cache Ratio:   ' . $val['get_hits'] . ' hits, ' . $val['get_misses'] . ' misses'));
    $cli->writeln($cli->indent('Connections:   ' . $val['curr_connections']));
    $cli->writeln($cli->indent('Traffic:       ' . sprintf("%0.2f", $val['bytes_read'] / 1048576) . ' MB in, ' . sprintf("%0.2f", $val['bytes_written'] / 1048576) . ' MB out'));
}
