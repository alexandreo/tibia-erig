<?php
namespace tibiaerig;
	
class Whoisonline {

    static function getWhoisonline($worlds) {
    	foreach ($worlds as $world) {
            $onlines =  array();
            $ch = curl_init();
            $timeout = 0;
            curl_setopt($ch, CURLOPT_URL, 'http://www.tibia.com/community/?subtopic=worlds&world='.$world);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $conteudo = curl_exec ($ch);
            curl_close($ch);
//			</td><td style="width:10%;" >51</td><td style="width:20%;" >Royal&#160;Paladin</td></tr>
            preg_match_all('#<a href=".*?subtopic=characters&name=.*?" >(.*?)</a></td><td style="width:10%;" >(.*?)</td><td style="width:20%;" >(.*?)</td></tr>#s', $conteudo, $m);
            foreach ($m[1] as $k => $charname) {
                    $onlines[] = array('world' => $world, 'charname' => addslashes(str_replace('&#160;', ' ', $charname)), 'level' => $m[2][$k] ,'voc' => str_replace('&#160;', ' ', $m[3][$k]));
            }
        }
        return $onlines;
    }
}
