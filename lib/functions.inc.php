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
 *   @copyright       2013, Black Cat Development
 *   @link            http://blackcat-cms.org
 *   @license         http://www.gnu.org/licenses/gpl.html
 *
 */

include dirname(__FILE__).'/../wblib/wbQuery.php';

function init($c)
{
    if(isset($_POST['pidkey']))
        thaw($c,$_POST['pidkey']);

    if(!isset($c['PATHS_OK']))
    {
        $wb_path = $bc_path = $wb_db_port = '';
        // try some defaults
        if(!isset($_POST['wb_path']) && file_exists(dirname(__FILE__).'/../wbdemo'))
            $wb_path = sanitizePath(dirname(__FILE__).'/../wbdemo');
        elseif(isset($_POST['wb_path']) && file_exists(dirname(__FILE__).'/../wb'))
            $wb_path = sanitizePath(dirname(__FILE__).'/../wb');
        if(!isset($_POST['bc_path']) && file_exists(dirname(__FILE__).'/../bcdemo'))
            $bc_path = sanitizePath(dirname(__FILE__).'/../bcdemo');
        elseif(!isset($_POST['bc_path']) && file_exists(dirname(__FILE__).'/../bc'))
            $bc_path = sanitizePath(dirname(__FILE__).'/../bc');
        // replace with post data
        if(isset($_POST['wb_path']))
            $wb_path = sanitizePath($_POST['wb_path']);
        if(isset($_POST['bc_path']))
            $bc_path = sanitizePath($_POST['bc_path']);
        if(isset($_POST['wb_db_port']))
            $wb_db_port = $_POST['wb_db_port'];
        // save results
        $c->offsetSet('VARS',array('wb_path'=>$wb_path,'wb_db_port'=>$wb_db_port,'bc_path'=>$bc_path));
        $c->offsetSet('SESS',isset($_POST['pidkey']) ? $_POST['pidkey'] : time());
        // check paths
        if(is_dir($wb_path) && is_dir($bc_path))
            $c->offsetSet('PATHS_OK',true);
        freeze($c,$c->offsetGet('SESS'));
    }
}   // end function init()

function freeze($c,$pid)
{
    $keys = $c->keys();
    $data = array();
    foreach($keys as $key)
        $data[$key] = $c->offsetGet($key);
    if(!is_dir(dirname(__FILE__).'/../backup'))
        mkdir(sanitizePath(dirname(__FILE__).'/../backup'),0777);
    $fh = fopen(sanitizePath(dirname(__FILE__).'/../backup/key_'.$pid.'.txt'),'w');
    fwrite($fh,serialize($data));
    fclose($fh);
}   // end function freeze()

function thaw($c,$pid)
{
    $file = sanitizePath(dirname(__FILE__).'/../backup/key_'.$pid.'.txt');
    $fh   = fopen($file,'r');
    $f    = fread($fh,filesize($file));
    fclose($fh);
    $config = unserialize($f);
    foreach(array_keys($config) as $key)
        $c->offsetSet($key,$config[$key]);
}   // end function thaw()

/**
 * reads config.php file and saves the config data in global container
 **/
function LoadData($c)
{
    $wb_path = $c['VARS']['wb_path'];
    if(file_exists(sanitizePath($wb_path.'/config.php')))
    {
        ReadConfigFile($c,'wb',sanitizePath($wb_path.'/config.php'));
        connect($c,'wb');
        $c->offsetSet('WB_SET',getSettings($c,'wb'));
        $addons = getAddons($c,'wb');
        $c['tpl']->assign('addons',$addons);
        $c['tpl']->draw('addons');
    }
}   // end function LoadData()

function ShowTables($c)
{
    $wb_path = $c['VARS']['wb_path'];
    if(isset($_POST['modules']))
    {
        $addons = $_POST['modules'];
        $c->offsetSet('ADDONS',$addons);
    }
    if(isset($_POST['languages']))
    {
        $addons = $_POST['languages'];
        $c->offsetSet('LANGUAGES',$addons);
    }
    if(isset($_POST['templates']))
    {
        $addons = $_POST['templates'];
        $c->offsetSet('TEMPLATES',$addons);
    }
    
    freeze($c,$c->offsetGet('SESS'));
    if(file_exists(sanitizePath($wb_path.'/config.php')))
    {
        include dirname(__FILE__).'/config.inc.php';
        ReadConfigFile($c,'wb',sanitizePath($wb_path.'/config.php'));
        connect($c,'wb');
        $handle = $c->offsetGet('wb_db');
        $tables = $handle->showTables();
        $inuse  = array();
// Brax_HighSlide_Gallery
// wb_mod_brax_highslide_images
        foreach($tables as $i => $t) 
        {
            // remove prefix
            $t        = $tables[$i] = str_replace($c['WB']['TABLE_PREFIX'],'',$t);
            // remove mod_ (if it's not a core table)
            $mod_name = str_replace('mod_','',$t);
// Brax_HighSlide_Gallery
// brax_highslide_images
            foreach($mod_map as $k => $v)
                if(preg_match('~^'.$k.'_~i',$mod_name,$m))
                    $mod_name = $v;
            // check if it's a core table or we have an addon for this table
            if(
                substr($t,0,4) != 'mod_'
             || array_key_exists($t,$core)
             || (in_array(substr($mod_name,0,strpos($mod_name,'_')),$addons) || in_array($mod_name,$addons))
             || (in_array(strtolower(substr($mod_name,0,strpos($mod_name,'_'))),$addons) || in_array(strtolower($mod_name),$addons))
            ) {
                $inuse[$t] = 1;
            }
        }
        $c['tpl']->assign('tables',$tables);
        include dirname(__FILE__).'/config.inc.php';
        $c['tpl']->assign('bl',$blacklist_tables);
        $c['tpl']->assign('inuse',$inuse);
        $c['tpl']->draw('tables');
    }
}   // end function ShowTables()

/**
 * export tables
 **/
function ExportSQL($c)
{
    $wb_path = $c['VARS']['wb_path'];
    if(isset($_POST['tables']) && file_exists(sanitizePath($wb_path.'/config.php')))
    {
        $tables  = $_POST['tables'];
        $c->offsetSet('TABLES',$tables);
        freeze($c,$c->offsetGet('SESS'));
        $sql = array();
        ReadConfigFile($c,'wb',sanitizePath($wb_path.'/config.php'));
        connect($c,'wb');
        $handle = $c->offsetGet('wb_db');
        $addons = $c->offsetGet('ADDONS');
        if($c->offsetExists('LANGUAGES'))
            $addons = array_merge($addons,$c->offsetGet('LANGUAGES'));
        if($c->offsetExists('TEMPLATES'))
            $addons = array_merge($addons,$c->offsetGet('TEMPLATES'));
        if(!is_dir(dirname(__FILE__).'/../backup'))
            mkdir(sanitizePath(dirname(__FILE__).'/../backup'),0777);
        if(!is_dir(dirname(__FILE__).'/../backup/'.$c->offsetGet('SESS')))
            mkdir(sanitizePath(dirname(__FILE__).'/../backup/'.$c->offsetGet('SESS')),0777);
        foreach($_POST['tables'] as $t)
        {
            $tname = $c['WB']['TABLE_PREFIX'].$t;
            $sql   = array();
            $item  = $handle->dumpTable($tname);
            switch ($t)
            {
// ----- addons table -----
                case 'addons':
                    list($tokens,$sql) = removeCreate($item);
                    foreach($tokens as $line)
                    {
                        foreach($addons as $addon)
                        {
                            if(preg_match("~\'".$addon."\'~i",$line))
                            {
                                // remove ID
                                $line = preg_replace('~^\(\'\d+\',~','(NULL,',$line);
                                $sql[] = $line;
                            }
                        }
                    }
                    $item = implode("\n",$sql)."\n";
                    break;
// ----- groups table -----
                case 'groups':
                case 'users':
                    list($tokens,$sql) = removeCreate($item);
                    // skip ('1', 'Administrators',...
                    foreach($tokens as $line)
                    {
                        if($line != '' && !preg_match('~\'Administrators\'~',$line) && !preg_match('~\'admin\'~',$line) )
                        {
                            $line  = preg_replace('~^\(\'\d+\',~','(NULL,',$line);
                            $sql[] = $line;
                        }
                    }
                    break;
// ----- pages table -----
                case 'pages':
                    list($tokens,$sql) = removeCreate($item);
                    foreach($tokens as $line)
                        $sql[] = $line;
                    array_unshift($sql,'TRUNCATE TABLE `'.$tname.'`;');
                    array_push($sql,'SELECT @max := MAX(`addon_id`)+ 1 FROM `'.$tname.'`;');
                    array_push($sql,'ALTER TABLE `'.$tname.'` auto_increment=@max;');
                    break;
// ----- search/sections table -----
                case 'search':
                case 'sections':
                    list($tokens,$sql) = removeCreate($item);
                    foreach($tokens as $line)
                        $sql[] = $line;
                    break;
// ----- settings table -----
                case 'settings':
                    list($tokens,$ignore) = removeCreate($item);
                    $skip = array(
                        'app_name','sec_anchor',
                        'warn_page_leave','default_theme','smart_login',
                        'default_time_format','default_date_format',
                        'rename_files_on_upload','operating_system',
                        'fingerprint_with_ip_octets','secure_form_module',
                        'wysiwyg_style','wysiwyg_editor','er_level',
                    );
                    $prefix  = array('wbmailer_','wb_secform_','wb_');
                    $regexp  = implode('|',$skip);
                    $regexp2 = implode('|',$prefix);
                    foreach($tokens as $line)
                    {
                        if($line != '' && !preg_match('~\'('.$regexp.')\'~',$line) && !preg_match('~\'('.$regexp2.')[^\'].+\'~',$line))
                        {
                            // ('4', 'website_title', 'WB 2.8.3 Testinstallation'),
                            $m = explode('\', \'', str_replace(array('(\'','\'),','\');'),array('','',''),$line));
                            $sql[] = 'UPDATE `'.$tname.'` SET `value`="'.$m[2].'" WHERE `name`="'.$m[1].'";';
                        }
                    }
                    break;
            }
            if(is_array($sql) && count($sql))
                if(count($sql)>1)
                    $item = implode("\n",$sql)."\n";
                else
                    $item = NULL;
// ----- TODO ----
// check if last char is a ;
// ----- TODO ----
            if($item)
            {
                $fh = fopen(sanitizePath(dirname(__FILE__).'/../backup/'.$c->offsetGet('SESS').'/'.$t.'.sql'),'w');
                fwrite($fh,$item);
                fclose($fh);
            }
        }
        $c['tpl']->assign('success',true);
        $c['tpl']->assign('message',$c['l']->t('Tables exported successfully'));
        // check if all exported modules are already installed in BC
        $bc_path = $c['VARS']['bc_path'];
        if(file_exists(sanitizePath($bc_path.'/config.php')))
        {
            ReadConfigFile($c,'bc',sanitizePath($bc_path.'/config.php'));
            connect($c,'bc');
        }
        $toinst = getMissingAddons($c);
        $c['tpl']->assign('install',$toinst);
        $c['tpl']->draw('sqlresult');
    }
}   // end ExportSQL()

/**
 * read config file and settings table
 **/
function ReadConfigFile($c,$prefix,$file)
{
    $cfg_temp = implode('###',file($file));
    $cfg      = array();
    foreach(array('DB_HOST','DB_NAME','DB_USERNAME','DB_PASSWORD','TABLE_PREFIX','DB_PORT') as $key)
    {
        if($prefix=='bc') $key = 'CAT_'.$key;
        preg_match('#define\(\''.$key.'\',\s*?\'(.+)\'#i',$cfg_temp,$match);
        if(is_array($match) && isset($match[1]))
            $cfg[str_replace('CAT_','',$key)] = $match[1];
    }
    if($prefix=='wb')
        if(isset($c['VARS']['wb_db_port']))
            $cfg['DB_PORT'] = $c['VARS']['wb_db_port'];
    if(count($cfg))
        $c->offsetSet(strtoupper($prefix),$cfg);
}   // end function ReadConfigFile()

/**
 * get the list of installed addons from the db; also checks if the addon is
 * in use (works for page type, languages, and templates)
 **/
function getAddons($c,$key)
{
    $var    = $key.'_db';
    $handle = $c->offsetGet($var);
    if(!is_resource($handle)) connect($c,$key);
    $addons = $handle->search(
        array(
            'tables'   => 'addons',
            'order_by' => 'type ASC, directory ASC'
        )
    );

    if(is_array($addons) && count($addons))
    {
        include dirname(__FILE__).'/config.inc.php';
        foreach($addons as $i => $addon)
        {
            $addons[$i]['skip']  = false;
            $addons[$i]['inuse'] = false;
            if(array_key_exists($addon['directory'],$blacklist_addons) || $addon['function'] == 'theme')
            {
                $addons[$i]['skip']     = true;
                $addons[$i]['skip_why'] = isset($blacklist_addons[$addon['directory']]) ? $blacklist_addons[$addon['directory']] : NULL;
                if(!$addons[$i]['skip_why'] && $addon['function'] == 'theme')
                    $addons[$i]['skip_why'] = 'Incompatible (Backend Theme)';
            }
            if(checkUse($c,$key,$addon['type'],$addon['directory']))    $addons[$i]['inuse'] = true;
        }
    }

    return $addons;
}   // end function getAddons()

/**
 * read settings table and convert to name => value array
 **/
function getSettings($c,$key)
{
    $data   = array();
    $var    = $key.'_db';
    $handle = $c->offsetGet($var);
    if(!is_resource($handle)) connect($c,$key);
    $result = $handle->search(
        array(
            'tables' => 'settings',
        )
    );
    if(count($result))
        foreach($result as $item)
            $data[$item['name']] = $item['value'];
    return $data;
}   // end function getSettings()

/**
 * find out if there are pages for given lang
 **/
function getPagesByLang($c,$key,$lang)
{
    $var    = $key.'_db';
    $handle = $c->offsetGet($var);
    if(!is_resource($handle)) connect($c,$key);
    $result = $handle->search(
        array(
            'tables' => 'pages',
            'where'  => 'language=?',
            'params' => array($lang)
        )
    );
    return ( count($result) ? true : false );
}

function getTemplateUse($c,$key,$tpl)
{
    $var    = $key.'_db';
    $handle = $c->offsetGet($var);
    if(!is_resource($handle)) connect($c,$key);
    $result = $handle->search(
        array(
            'tables' => 'pages',
            'where'  => 'template=?',
            'params' => array($tpl)
        )
    );
    return ( count($result) ? true : false );
}

function getMissingAddons($c)
{
    $bc_addons = getAddons($c,'bc');
    $wb_addons = $c->offsetGet('ADDONS');
    $handle    = $c->offsetGet('wb_db');
    $addons    = array();
    $toinst    = array();
    if(!is_resource($handle)) connect($c,'wb');
    foreach($bc_addons as $i => $addon)
        $addons[] = $addon['directory'];
    foreach($wb_addons as $addon)
    {
        if(!in_array($addon,$addons))
        {
            $ver = $handle->search(
                array(
                    'tables' => 'addons',
                    'where'  => 'directory=?',
                    'params' => array($addon),
                    'fields' => 'version'
                )
            );
            $toinst[] = $addon
                      . (
                            count($ver)
                          ? ' (Version: '.$ver[0]['version'].')'
                          : ''
                        );
        }
    }
    return $toinst;
}

/**
 * checks if given addon is in use; works for page type modules, languages and
 * templates
 **/
function checkUse($c,$key,$type,$mod)
{
    $var    = $key.'_db';
    $setkey = strtoupper($key).'_SET';
    $handle = $c->offsetGet($var);
    if(!is_resource($handle)) connect($c,$key);
    if(!isset($c[$setkey]))
        $c->offsetSet($setkey,getSettings($c,$key));
    if($type=='module')
    {
        $result = $handle->search(
            array(
                'tables' => 'sections',
                'where'  => 'module=?',
                'params' => array($mod)
            )
        );
        return ( count($result) ? true : false );
    }
    if($type=='language')
        if( ( isset($c[$setkey]['default_language']) && $mod == $c[$setkey]['default_language'] ) || getPagesByLang($c,$key,$mod))
            return true;
        else
            return false;
    if($type=='template')
        if( ( isset($c[$setkey]['default_template']) && $mod == $c[$setkey]['default_template'] ) || getTemplateUse($c,$key,$mod) )
            return true;
        else
            return false;
}   // end function checkUse()


/**
 * connect to the DB
 **/
function connect($c,$key)
{
    $handle = \wblib\wbQuery::getInstance(
        array(
            'connection_name' => $key,
            'host'            => $c[strtoupper($key)]['DB_HOST'],
            'dbname'          => $c[strtoupper($key)]['DB_NAME'],
            'pass'            => $c[strtoupper($key)]['DB_PASSWORD'],
            'user'            => $c[strtoupper($key)]['DB_USERNAME'],
            'prefix'          => $c[strtoupper($key)]['TABLE_PREFIX'],
            'port'            => $c[strtoupper($key)]['DB_PORT'],
        )
    );
    if(is_object($handle))
        $c->offsetSet($key.'_db',$handle);
}

function removeCreate($item)
{
    $tokens  = preg_split("/(INSERT INTO )/im", $item, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sql     = array_slice($tokens,1,1); // skip CREATE
    $tokens  = array_slice($tokens,2);
    $tokens  = preg_split("~\r?\n~", implode("\n",$tokens));
    $sql[0] .= array_shift($tokens);
    return array($tokens,$sql);
}

/**
 * sanitize path
 *
 * @param string $path
 * @return string
 **/
function sanitizePath($path) {
    $path       = preg_replace( '~/{1,}$~', '', $path );
	$path       = str_replace( '\\', '/', $path );
    $path       = preg_replace('~/\./~', '/', $path);
    $path       = str_replace(array('"',"'"),array('',''),$path);
    $parts      = array();
    foreach ( explode('/', preg_replace('~/+~', '/', $path)) as $part )
        if ($part === ".." || $part == '')
            array_pop($parts);
        elseif ($part!="")
            $parts[] = $part;
    $new_path = implode("/", $parts);
    if ( ! preg_match( '/^[a-z]\:/i', $new_path ) )
		$new_path = '/' . $new_path;
    return $new_path;
}   // end function sanitizePath()
