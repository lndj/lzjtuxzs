<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-13
 * Time: 下午6:58
 */
namespace Lib\Jiaowu;
/**
 * Class ArrayAnalysis
 * @package Lib\Jiaowu
 * HTML解析类
 */
class ArrayAnalysis{

    public static function get_td_array($table) {

        $table = preg_replace("/<table[^>]*?>/is","",$table);
        $table = preg_replace("/<tr[^>]*?>/si","",$table);
        $table = preg_replace("/<td[^>]*?>/si","",$table);
        $table = str_replace("</tr>","{tr}",$table);
        $table = str_replace("</td>","{td}",$table);
        //去掉 HTML 标记
        $table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);
        //去掉空白字符
        $table = preg_replace("'([rn])[s]+'","",$table);
        $table = str_replace(" ","",$table);
        $table = str_replace(" ","",$table);
        $table = str_replace("&nbsp;","",$table);

        $table = explode('{tr}', $table);
        array_pop($table);
        foreach ($table as $key=>$tr) {
            $td = explode('{td}', $tr);
            $td = explode('{td}', $tr);
            array_pop($td);
            $td_array[] = $td;
        }
        return @$td_array;
    }

    public  static function get_kb_array($table) {            //匹配课表

        $table = preg_replace("/<table[^>]*?>/is","",$table);
        $table = preg_replace("/<tr[^>]*?>/si","",$table);
        $table = preg_replace("/<td[^>]*?>/si","",$table);
        $table = str_replace("</tr>","{tr}",$table);
        $table = str_replace("</td>","{td}",$table);
        $table = str_replace("<br><br>","\n\n",$table);
        $table = str_replace("<br>","\n",$table);
        //去掉 HTML 标记
        $table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);
        //去掉空白字符
        $table = preg_replace("'([rn])[s]+'","",$table);
        $table = str_replace(" ","",$table);
        $table = str_replace(" ","",$table);
        $table = str_replace("&nbsp;","",$table);

        $table = explode('{tr}', $table);
        array_pop($table);
        foreach ($table as $key=>$tr) {
            $td = explode('{td}', $tr);
            $td = explode('{td}', $tr);
            array_pop($td);
            $td_array[] = $td;
        }
        return $td_array;
    }

}