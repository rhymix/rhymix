<?php

/**
 * Do not allow execution over the web.
 */
if (PHP_SAPI !== 'cli')
{
    exit(1);
}

/**
 * Download the latest IPv4 data from libkrisp.
 */
$download_url = 'https://mirror.oops.org/pub/oops/libkrisp/data/v2/krisp.csv.gz';
$referer_url = 'https://mirror.oops.org/pub/oops/libkrisp/data/v2/';
$content = file_get_contents($download_url, false, stream_context_create(array(
    'http' => array(
        'user_agent' => 'Mozilla/5.0 (compatible; IP range generator)',
        'header' => 'Referer: ' . $referer_url . "\r\n",
    ),
)));
$content = gzdecode($content);
if (!$content)
{
    exit(2);
}

/**
 * Load IP range data.
 */
$ranges = array();
$content = explode("\n", $content);
foreach ($content as $line)
{
    $line = explode("\t", $line);
    if (count($line) < 2) continue;
    $start = trim($line[0]);
    $end = trim($line[1]);
    $ranges[$start] = array($start, $end);
}

/**
 * Sort the ranges.
 */
ksort($ranges);
$ranges = array_values($ranges);
$count = count($ranges);

/**
 * Merge adjacent ranges.
 */
for ($i = 0; $i < $count; $i++)
{
    if ($i == 0) continue;
    $previous_i = $i - 1;
    while (true)
    {
        if ($ranges[$previous_i] !== null) break;
        $previous_i--;
    }
    
    if ($ranges[$i][0] == $ranges[$previous_i][1] + 1)
    {
        $ranges[$previous_i][1] = $ranges[$i][1];
        $ranges[$i] = null;
    }
}

/**
 * Organize into final format.
 */
$ranges_final = array();
foreach ($ranges as $range)
{
    if ($range !== null) $ranges_final[] = $range;
}

/**
 * Save to file.
 */
$content = '<' . '?php' . "\n\n" . '/**' . "\n" . ' * Source: ' . $referer_url . "\n";
$content .= ' * Last Updated: ' . date('Y-m-d') . "\n" . ' */' . "\n";
$content .= 'return ' . var_export($ranges_final, true) . ';' . "\n";
file_put_contents(__DIR__ . '/../korea.ipv4.php', $content);

/**
 * Report status.
 */
echo count($ranges_final) . ' IPv4 ranges saved.' . PHP_EOL;
