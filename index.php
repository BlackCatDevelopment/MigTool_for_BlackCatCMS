<?php

/**
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or (at
 *   your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful, but
 *   WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 *   General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 *   @author          Black Cat Development
 *   @copyright       2014, Black Cat Development
 *   @link            http://blackcat-cms.org
 *   @license         http://www.gnu.org/licenses/gpl.html
 *
 */

include dirname(__FILE__).'/lib/Pimple.php';
include dirname(__FILE__).'/lib/rain.tpl.class.php';
include dirname(__FILE__).'/wblib/wbLang.php';
include dirname(__FILE__).'/lib/functions.inc.php';

use \Pimple;
$c = new Pimple();
$c['VARS'] = array();
$c['WB']   = array();
$c['BC']   = array();
$c['SESS'] = NULL;

init($c);

use \wblib\wbLang;
$l = \wblib\wbLang::getInstance();
$c->offsetSet('l',$l);

use \RainTPL;
RainTPL::$tpl_dir = dirname(__FILE__).'/templates/';
RainTPL::$tpl_ext = 'txt';
RainTPL::$langh   = $l;
$t = new RainTPL();

$c->offsetSet('tpl',$t);

$t->draw('header');
$t->assign('action',$_SERVER['SCRIPT_NAME']);
$t->assign('pidkey',$c->offsetGet('SESS'));

if(isset($_POST['do']))
{
    thaw($c,$_POST['pidkey']);
    $t->assign($c->offsetGet('VARS'));
    switch ($_POST['do'])
    {
        case 'step2':
            LoadData($c);
            break;
        case 'step3':
            ShowTables($c);
        	break;
        case 'step4':
            ExportSQL($c);
            break;
        case 'step5':
            Import($c);
            break;
        default:
            $tpl = implode('',file('./templates/error.txt'));
            echo str_replace(
                '{{errmsg}}',
                'Invalid switch: '.$_POST['do'],
                $tpl
            );
            break;
    }
}
else
{
    $files = array();
    // check for existing config files
    if (false !== ($dh = opendir(dirname(__FILE__).'/backup')))
    {
        while( false !== ($file = readdir($dh)))
        {
            if( $file == '.' || $file == '..' ) continue;
            if(preg_match('~^key_(.+)\.txt~i',$file,$m))
            {
                if(is_dir(dirname(__FILE__).'/backup/'.$m[1]))
                    $files[] = $file;
                else
                    unlink(dirname(__FILE__).'/backup/'.$file);
            }
        }
    }
    $t->assign($c->offsetGet('VARS'));
    $t->assign('files',$files);
    freeze($c,$c->offsetGet('SESS'));
    $t->draw('form');
}