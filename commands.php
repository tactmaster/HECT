<?php
/*
    This file is part of TransPHPRSS.

    TransPHPRSS is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.

*/
require_once 'setting.php';
function loadSettings() {
    if (file_exists("setting.json"))
    {
    $filedata = file_get_contents("setting.json");
    $json = json_decode($filedata, true);
    
    //var_dump($json);
    if (function_exists("json_last_error")) {
        $error = json_last_error();

        $josn_errors = array("JSON_ERROR_NONE   No error has occurred",
            "JSON_ERROR_DEPTH       The maximum stack depth has been exceeded",
            "JSON_ERROR_CTRL_CHAR   Control character error, possibly incorrectly encoded",
            "JSON_ERROR_STATE_MISMATCH      Invalid or malformed JSPN",
            "JSON_ERROR_SYNTAX      Syntax error",
            "JSON_ERROR_UTF8        Malformed UTF-8 characters, possibly incorrectly encoded");
        if ($error > 0)
        {
        echo "JOSN Load Error " + $error + ":" + $josn_errors[$error] + "\n";
        }
    }
    //$settings = $json;
  $settings = array_to_object( $json,new Setting());
    }
    else
    {
        $settings = new Setting();
    }
//var_dump($settings);
    return $settings;
}

function saveSettings($setting) {
        $fh = fopen("setting.json", 'w');
		
	
        //var_dump($setting);
        $json = indent(json_encode($setting));
        //print $json;
        fwrite($fh,$json );
        fclose($fh);
}

function array_to_object($array = array(),$data = false) {
    if (!empty($array)) {
        
        foreach ($array as $akey => $aval) {
            $data -> {$akey} = $aval;
        }
        return $data;
    }
    return false;
}

/**

 * 
 *  * Indents a flat JSON string to make it more human-readable.
 *  * 
 * @param string $json The original JSON string to process.
 * @link http://recursive-design.com/blog/2008/03/11/format-json-with-php/
 * @return string Indented version of the original JSON string.
 */
function indent($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        
        // If this character is the end of an element, 
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        $prevChar = $char;
    }

    return $result;
}


?>
