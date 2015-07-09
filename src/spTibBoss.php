<?php
namespace TibiaErig;	    

//usage: $table = spTibExperience::getExperienceTable(array('Antica', 'Pacera'));
//if any element in $table has less than 300 entries then there was a pretty serious failure (since it automatically retries)

class spTibBosses {
    const PAGES = 0;
    const PERPAGE = 0;
    const MAXTIME = 18000; // keep trying for 5 hours, enough time for maintenance/update/etc. to run, in theory..
    const TIMEOUT = 30; // seconds
    const MAXBACKOFF = 600; // maximum "backoff" in case of failure

    static function getBosses($worlds = array()) {
       
        $start_t = time();
        $table = array();
        $failcnt = 0;
        $output = array();

        do {
            $failures = array();

            if (!function_exists('curl_multi_init')) {
                error_log('spTibExperience requires the cURL PHP extension');
                return false;
            }

            foreach ($worlds as $world) {
                $mh = curl_multi_init();
                $chs = array();
                if (!isset($output[$world])) $output[$world] = array();
                for ($page = 0; $page <= self::PAGES; $page++) {
                    if (isset($output[$world][$page]))
                        continue;
                    $chs[$page] = curl_init();
                    curl_setopt($chs[$page], CURLOPT_URL, $url = 'http://www.tibia.com/community/?subtopic=killstatistics');
                    curl_setopt($chs[$page], CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chs[$page], CURLOPT_FRESH_CONNECT, true);
                    curl_setopt($chs[$page], CURLOPT_POST, true);
					curl_setopt($chs[$page], CURLOPT_POSTFIELDS, array('world' => $world));
                    curl_setopt($chs[$page], CURLOPT_TIMEOUT, self::TIMEOUT);
                    curl_multi_add_handle($mh, $chs[$page]);
                    echo $world;
                    if (@$GLOBALS['debug']) { error_log("preparing to fetch $url"); }
                }

                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($active);

                for ($page = 0; $page <= self::PAGES; $page++) {
                    if (isset($output[$world][$page]))
                        continue;

                    $out = curl_multi_getcontent($chs[$page]);
                    curl_close($chs[$page]);

                    $failure = false;
                    /* if (preg_match_all('#<TD WIDTH=10%>(.*?)</TD>.*?subtopic=characters&name=.*?">(.*?)</A></TD><TD WIDTH=15%>(.*?)</TD><TD WIDTH=20%>(.*?)</TD></TR>#s', $out, $m)) {*/
                    if (preg_match_all('#<TR BGCOLOR=#D4C0A1><TD>(.*?)</TD><TD ALIGN=right>(.*?)</TD><TD ALIGN=right>(.*?)</TD><TD ALIGN=right>(.*?)</TD><TD ALIGN=right>(.*?)</TD></TR>#s', $out, $m)) {
                        if (count($m[2]) != self::PERPAGE) {
                            if (@$GLOBALS['debug']) { error_log("on $world page $page, found " . count($m[2]) . ", not " . self::PERPAGE); }
                            $failure = true;
                        }
/*
                        else if (rand(1, 2) == 1) {
                            if (@$GLOBALS['debug']) { error_log("failing because i can"); }
                            $failure = true;
                        }
*/
                        else {
                            foreach ($m[2] as $k => $name) {
                                $rank = $m[1][$k];
                                $level = $m[3][$k];
                                $xp = $m[4][$k];
                                if (!isset($table[$world])) $table[$world] = array();
                               //$table[$world][$rank] = array('name' => $name, 'level' => $level, 'xp' => $xp);
                               $table[] = array('name' => $name, 'level' => $level, 'xp' => $xp, 'world' => $world, 'rank' => $rank);
                            }
                            $output[$world][$page] = true;
                        }
                    }
                    else {
                        $failure = true;
                    }

                    if ($failure) {
                        $now_t = time();

                        if (@$GLOBALS['debug']) { error_log("failure for $world page $page"); }

                        if ($now_t - $start_t >= self::MAXTIME)
                            $output[$world][$page] = true;
                    }
                }

                curl_multi_close($mh);

                if (count($output[$world]) <= self::PAGES)
                    $failures[$world] = true;
                else {
                    ksort($output[$world]);
                    unset($failures[$world]);
                }
            }

            if (count($failures)) {
                $failcnt++;

                $backoff = min(self::MAXBACKOFF, pow(2, $failcnt));

                // don't want pow(2, ...) to get too big!
                if (pow(2, $failcnt) > self::MAXBACKOFF)
                    $failcnt--;

                if (@$GLOBALS['debug']) { error_log("backing off for $backoff seconds. will keep trying for " . (self::MAXTIME - ($now_t - $start_t)) . " seconds"); }
                sleep($backoff);
            }
            else {
                foreach ($table as $world => $ranks)
                    ksort($table[$world]);
                	ksort($table);
                break;
            }
        } while (1);
        return $table;
    }
}

?>