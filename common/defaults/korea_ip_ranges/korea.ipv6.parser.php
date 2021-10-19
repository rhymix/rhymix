<?php

/**
 * Do not allow execution over the web.
 */
if (PHP_SAPI !== 'cli')
{
    exit (1);
}

/**
 * Download the latest IPv6 data from KISA.
 */
$download_url = 'https://krnic.or.kr/jsp/statboard/IPAS/inter/sec/ipv6AddrListExcel.jsp';
$referer_url = 'https://krnic.or.kr/jsp/statboard/IPAS/inter/sec/currentV6Addr.jsp';
$content = file_get_contents($download_url, false, stream_context_create(array(
    'http' => array(
        'user_agent' => 'Mozilla/5.0 (compatible; IP range generator)',
        'header' => 'Referer: ' . $referer_url . "\r\n",
    ),
)));
if (!$content)
{
    exit (2);
}

/**
 * Parse the HTML/Excel document.
 */
$regex = '#<tr>\\s*<td [^>]+>([0-9a-f:]+::)</td>\\s*<td [^>]+>(/[0-9]+)</td>#iU';
preg_match_all($regex, $content, $matches, PREG_SET_ORDER);

/**
 * Extract the address and netmask for each range.
 */
$ranges = array();
foreach ($matches as $match)
{
    $start = str_pad(str_replace(':', '', strtolower($match[1])), 16, '0', STR_PAD_RIGHT);
    $mask = str_repeat('f', ((64 - trim($match[2], '/')) / 4));
    $end = substr($start, 0, 16 - strlen($mask)) . $mask;
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
    
    if (hexdec($ranges[$i][0]) == hexdec($ranges[$previous_i][1]) + 1)
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
file_put_contents(__DIR__ . '/../korea.ipv6.php', $content);

/**
 * Report status.
 */
echo count($ranges_final) . ' IPv6 ranges saved.' . PHP_EOL;
