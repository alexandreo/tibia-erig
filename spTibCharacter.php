<?php
namespace spTibCharacter;	
	
class spTibCharacter {
    const ATTEMPTS = 5;
    const TIMEOUT = 30; 

    static function getCharacter($names) {
        $table = array();

        do {
            if (!function_exists('curl_multi_init')) {
                error_log('curl nao instalado :)');
                return false;
            }

            $mh = curl_multi_init();
            $chs = array();

            foreach ($names as $name) {
                $chs[$name] = curl_init();
                curl_setopt($chs[$name], CURLOPT_URL, $url = sprintf('http://www.tibia.com/community/?subtopic=characters&name=%s', urlencode($name)));
                curl_setopt($chs[$name], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chs[$name], CURLOPT_FRESH_CONNECT, true);
                curl_setopt($chs[$name], CURLOPT_TIMEOUT, self::TIMEOUT);
                curl_multi_add_handle($mh, $chs[$name]);

                if (@$GLOBALS['debug']) { error_log("erro ao acessar a $url"); }
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($active);

            foreach ($names as $name) {
                $out = curl_multi_getcontent($chs[$name]);
                curl_close($chs[$name]);

                $failure = false;

                if (preg_match('@<TR><TD BGCOLOR="#505050" CLASS=white><B>Could not find character</B></TD></TR>@s', $out)) {
                    $table[$name] = array('error' => 'Could not find character');
                }
                else if (preg_match('@<td width=20%>Name:</td><td>(.+?)<div style="float: right">@s', $out, $m)) {
                    $table[$name]['Charname'] = trim(addslashes($m[1]));
					if (preg_match('@<td>Sex:</td><td>(.+?)</td></tr>@s', $out, $Sex)) {
						  $table[$name]['Sex'] = $Sex[1];
					}
					if (preg_match('@<td>Vocation:</td><td>(.+?)</td></tr>@s', $out, $Vocation)) {
						  $table[$name]['Vocation'] = $Vocation[1];
					}
					if (preg_match('@<td>Level:</td><td>(.+?)</td></tr>@s', $out, $Level)) {
						  $table[$name]['Level'] = $Level[1];
					}					
					
					if (preg_match('@<td>World:</td><td>(.+?)</td>@s', $out, $World)) {
						  $table[$name]['World'] = $World[1];
					}
					if (preg_match('@<td>Former World:</td><td>(.+?)</td></tr>@s', $out, $FormerWorld)) {
						  $table[$name]['FormerWorld'] = $FormerWorld[1];
					}
					if (preg_match('@<td>House:</td><td>(.+?)</td></tr>@s', $out, $House)) {
						  $table[$name]['House'] = html_entity_decode(addslashes(utf8_encode($House[1])));
					}					
				
					if (preg_match('@<td>Residence:</td><td>(.+?)</td></tr>@s', $out, $Residence)) {
						  $table[$name]['Residence'] = addslashes($Residence[1]);
					}					
					if (preg_match('@<td>Account&#160;Status:</td><td>(.+?)</td></tr>@s', $out, $AccountStatus)) {
						  $table[$name]['AccountStatus'] = $AccountStatus[1];
					}							
					if (preg_match('@<td valign=top>Comment:</td><td>(.+?)</td></tr>@s', $out, $Comment)) {
						  $table[$name]['Comment'] = addslashes($Comment[1]);
					}						
					//<td width="25%" valign="top" >(.*?)</td><td>(.*?)</td>
					if (preg_match_all('#<td width="25%" valign="top" >(.*?)</td><td>(.*?)</td>#s', $out, $DeathList) ) {
                        foreach ($DeathList[2] as $k => $nulo) {
                           $table[$name]['DeathList'][]  = array( 'DateTime' => html_entity_decode($DeathList[1][$k]), 'Died' => html_entity_decode($DeathList[2][$k]) );
                        }
					}
					
    			} else {
                    $table[$name] = array('error' => 'Unknown error');
                }

            }

            curl_multi_close($mh);
        } while (0);

        return $table;
    }
}
//