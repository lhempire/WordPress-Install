<?php

// 全局配置
set_time_limit( 0 );
error_reporting( E_ALL );

// GitHub信息
define( 'GITHUB_USERNAME' , 'lhempire' );
define( 'GITHUB_PROJECT'  , 'WordPress-Install' );

// 版本信息
define( 'WPRI_VERSION'    , '1.0.0' );

// 建议的插件和主题
$suggestions = array(

  # 可以是每个插件的URL数组，也可以是文本文件的字符串URL，并在新行上为每个插件提供URL
  plugins' => 'http://' . GITHUB_USERNAME . '.github.io/' . GITHUB_PROJECT .'/list-plugin.txt

 # 可以是每个主题的URL数组，也可以是文本文件的字符串URL，并在新行中为每个主题提供URL
 'themes'  => 'http://' . GITHUB_USERNAME . '.github.io/' . GITHUB_PROJECT .'/list-theme.txt'

);

// 函数提取
function extractSubFolder( $zipFile , $target = null , $subFolder = null ){
  if( is_null( $target ) )
    $target = dirname( __FILE__ );
  $zip = new ZipArchive;
  $res = $zip->open( $zipFile );
  if( $res === TRUE ){
    if( is_null( $subFolder ) ){
      $zip->extractTo( $target );
    }else{
      for( $i = 0 , $c = $zip->numFiles ; $i < $c ; $i++ ){
        $entry = $zip->getNameIndex( $i );
        //Use strpos() to check if the entry name contains the directory we want to extract
        if( $entry!=$subFolder.'/' && strpos( $entry , $subFolder.'/' )===0 ){
          $stripped = substr( $entry , 9 );
          if( substr( $entry , -1 )=='/' ){
           // Subdirectory
            $subdir = $target.'/'.substr( $stripped , 0 , -1 );
            if( !is_dir( $subdir ) )
              mkdir( $subdir );
          }else{
            $stream = $zip->getStream( $entry );
            $write = fopen( $target.'/'.$stripped , 'w' );
            while( $data = fread( $stream , 1024 ) ){
              fwrite( $write , $data );
            }
            fclose( $write );
            fclose( $stream );
          }
        }
      }
    }
    $zip->close();
    return true;
  }
  die( 'Unable to open '.$zipFile );
  return false;
}

// Function to Cleanse Webroot
function rrmdir( $dir ){
  if( is_dir( $dir ) ){
    $objects = scandir( $dir );
    foreach( $objects as $object ){
      if( $object!='.' && $object!='..' ){
        if( filetype( $dir.'/'.$object )=='dir' )
          rrmdir( $dir.'/'.$object );
        else
          unlink( $dir.'/'.$object );
      }
    }
    reset( $objects );
    rmdir( $dir );
  }else{
    unlink( $dir );
  }
}
function cleanseFolder( $exceptFiles = null ){
  if( $exceptFiles == null )
    $exceptFiles[] = basename( __FILE__ );
  $contents = glob('*');
  foreach( $contents as $c ){
    if( !in_array( $c , $exceptFiles ) )
      rrmdir( $c );
  }
}
function downloadFromURL( $url = null , $local = null ){
  $result = null;
  if( is_null( $local ) )
    $local = basename( $url );
  if( $content = @file_get_contents( $url ) ){
    $result = @file_put_contents( $local , $content );
  }elseif( function_exists( 'curl_init' ) ){
    $fp = fopen( dirname(__FILE__) . '/' . $local , 'w+' );
    $ch = curl_init( str_replace( ' ' , '%20' , $url ) );
    curl_setopt($ch , CURLOPT_TIMEOUT        , 50 );
    curl_setopt($ch , CURLOPT_FILE           , $fp );
    curl_setopt($ch , CURLOPT_FOLLOWLOCATION , true );
    $result = curl_exec( $ch );
    curl_close( $ch );
    fclose( $fp );
  }else{
    $result = false;
  }
  return $result;
}
function getGithubVersion(){
  $versionURL = 'https://' . GITHUB_USERNAME . '.github.io/' . GITHUB_PROJECT .'/version.txt';
  $remoteVersion = null;
  if( !( $remoteVersion = @file_get_contents( $versionURL ) )
      && function_exists( 'curl_init' ) ){
    $ch = curl_init( str_replace( ' ' , '%20' , $versionURL ) );
    curl_setopt($ch , CURLOPT_TIMEOUT        , 50 );
	curl_setopt($ch , CURLOPT_RETURNTRANSFER , true );
	curl_setopt($ch , CURLOPT_HEADER         , false );
    curl_setopt($ch , CURLOPT_FOLLOWLOCATION , true );
    $remoteVersion = curl_exec( $ch );
    curl_close( $ch );
  }
  return $remoteVersion;
}

// Declare Parameters
$step = 0;
if( isset( $_POST['step'] ) )
  $step = (int) $_POST['step'];

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
<meta name="viewport" content="width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>WordPress &gt; 远程安装程序</title>
<link rel="stylesheet" id="combined-css" href="//<?php echo GITHUB_USERNAME; ?>.github.io/<?php echo GITHUB_PROJECT; ?>/style.css" type="text/css" media="all">
</head>
<body class="wp-core-ui">
<h1 id="logo"><a href="http://wordpress.org/">WordPress远程安装程序</a></h1>

<?php

switch( $step ){

  default :
  case 0 :

?>
<!-- STEP 0 //-->
<h1>WordPress远程安装程序</h1>
<p>WordPress远程安装程序是一个脚本，用于简化WordPress内容管理系统的安装。有些用户使用FTP的经验有限，有些网络主机不允许文件在上传后解压缩，有些人想让他们的WordPress安装得更快更简单。</p>
<p>使用WordPress远程安装程序很简单——上传一个PHP文件到你的服务器上，通过web浏览器访问它，然后按照提示完成7个简单的步骤，最后，WordPress安装程序就会启动。</p>
<?php
    if( version_compare( WPRI_VERSION , $githubVersion = getGithubVersion() , '<' ) ){
?>
<p class="version_alert">你使用的是 <?php echo WPRI_VERSION; ?>. 版本 <?php echo $githubVersion; ?> 可以通过 <a href="https://github.com/<?php echo GITHUB_USERNAME; ?>/<?php echo GITHUB_PROJECT; ?>">Github</a>.</p>
<?php
    }
?>
<form method="post">
  <input type="hidden" name="step" value="1" />
  <input type="submit" name="submit" value="Let's Get Started!" class="button button-large" />
</form>
<?php

    break;

  case 1 :

    if( isset( $_POST['action'] ) && $_POST['action']=='cleanse' )
      cleanseFolder();

    $tests = array(
      array(
        'result' => ini_get( 'allow_url_fopen' ) ,
        'pass' => '<strong>allow_url_open</strong> is Enabled' ,
        'fail' => '<strong>allow_url_open</strong> is Disabled'
      ) ,
      array(
        'result' => !count( array_diff( glob( '*' ) , array( basename( __FILE__ ) , 'version.txt' ) ) ) ,
        'pass' => 'The server is empty (apart from this file)' ,
        'fail' => 'The server is not empty.'
      )
    );
?>
<!-- STEP 1 //-->
<h1>步骤1/7:预安装检查</h1>
<?php
    if( isset( $_POST['action'] ) && $_POST['action']=='cleanse' ){
?>
<p>按要求从目录中删除所有文件。</p>
<?php
    }
?>
<ul>
<?php

    $proceed = true;
    foreach( $tests as $t ){
      if( !$t['result'] )
        $proceed = false;
?>
  <li class="<?php echo ( $t['result'] ? 'pass' : 'fail' ); ?>"><?php echo $t[( $t['result'] ? 'pass' : 'fail' )]; ?></li>
<?php
    }
?>
</ul>
<?php
    if( !$proceed ){
?>
<p>注:在上述问题解决前，我们无法继续进行。</p>
<form method="post">
  <input type="hidden" name="step" value="1" />
  <input type="hidden" name="action" value="cleanse" />
  <input type="submit" name="submit" value="Delete All Files from Directory to Proceed" class="button button-large confirm" data-msg="Are you sure? All files, Wordpress-related or not, will be removed. Delete files are unrecoverable." />
</form>
<?php
    }else{
?>
<form method="post">
  <input type="hidden" name="step" value="2" />
  <input type="submit" name="submit" value="Commence Install of WordPress" class="button button-large" />
</form>
<?php
    }

    break;

  case 2 :

?>
<!-- STEP 2 //-->
<h1>步骤2/7:安装Wordpress</h1>
<ul>
<?php
    $proceed = true;

    if( downloadFromURL( 'https://wordpress.org/latest.zip' , 'wordpress.zip' ) ){
?>
  <li class="pass">从Wordpress.org下载最新的WordPress - 成功</li>
<?php
    }else{
      $proceed = false;
?>
  <li class="fail">从Wordpress.org下载最新的WordPress -失败</li>
<?php
    }

    if( !$proceed ){
?>
  <li class="skip">提取WordPress -跳过</li>
<?php
    }elseif( extractSubFolder( 'wordpress.zip' , null , 'wordpress' ) ){
?>
  <li class="pass">提取WordPress - 成功</li>
<?php
    }else{
      $proceed = false;
?>
  <li class="fail">提取WordPress -失败</li>
<?php
    }

    if( !$proceed ){
?>
  <li class="skip">删除WordPress ZIP -跳过</li>
<?php
    }elseif( unlink( 'wordpress.zip' ) ){
?>
  <li class="pass">删除WordPress ZIP - 成功</li>
<?php
    }else{
      $proceed = false;
?>
  <li class="fail">删除WordPress ZIP - 失败</li>
<?php
    }
?>
</ul>
<?php

    if( !$proceed ){
?>
<p>注:在上述问题解决前，我们无法继续进行。</p>
<?php
    }else{
?>
<form method="post">
  <input type="hidden" name="step" value="3" />
  <input type="submit" name="submit" value="Next Step - Plugins" class="button button-large" />
</form>
<?php
    }

    break;

  case 3 :

    $suggest = '';
    if( is_array( $suggestions['plugins'] ) ){
      $suggest = implode( "\n" , $suggestions['plugins'] );
    }elseif( is_string( $suggestions['plugins'] ) ){
      if( !( $suggest = @file_get_contents( $suggestions['plugins'] ) ) )
        $suggest = '';
    }

?>
<!-- STEP 3 //-->
<h1>步骤3/7:安装插件</h1>
<p>列出所有WordPress插件的下载url，每行一个</p>
<form method="post">
  <textarea name="plugins"><?php echo $suggest; ?></textarea>
  <input type="hidden" name="step" value="4" />
  <input type="submit" name="submit" value="Install Plugins" class="button button-large" />
</form>
<?php

    break;

  case 4 :

?>
<!-- STEP 4 //-->
<h1>步骤4/7:安装插件</h1>
<ul>
<?php
    $plugin_result = ( !file_exists( @unlink( dirname( __FILE__ ).'/wp-content/plugins/hello.php' ) || dirname( __FILE__ ).'/wp-content/plugins/hello.php' ) );
?>
  <li class="<?php echo ( $plugin_result ? 'pass' : 'fail' ); ?>">删除不需要的“Hello Dolly”插件 - <?php echo ( $plugin_result ? 'OK' : 'FAILED' ); ?></li>
<?php
    if( isset( $_POST['plugins'] ) ){
      $plugins = array_filter( explode( "\n" , $_POST['plugins'] ) );
      foreach( $plugins as $url ){
        $plugin_result = false;
        $plugin_message = 'UNKNOWN';
        $url = trim( $url );
        if( strpos( $url , 'http' )!==0 )
          $url = 'http://'.$url;
        if( preg_match( '/^(https?\:\/\/?downloads\.wordpress\.org\/plugin\/)([^\.]+)((?:\.\d+)+)?\.zip$/' , $url , $bits ) )
          $url = $bits[1].$bits[2].'.zip';
        $get = @file_get_contents( $url );
        if( !$get ){
          $plugin_message = 'FAILED TO DOWNLOAD';
        }else{
          file_put_contents( 'temp_plugin.zip' , $get );
          if( !extractSubFolder( 'temp_plugin.zip' , dirname( __FILE__ ).'/wp-content/plugins' ) ){
            $plugin_message = 'FAILED TO EXTRACT';
          }else{
            $plugin_result = true;
            $plugin_message = 'OK';
          }
          @unlink( 'temp_plugin.zip' );
        }
?>
  <li class="<?php echo ( $plugin_result ? 'pass' : 'fail' ); ?>">安装 <strong><?php echo $bits[2]; ?></strong> - <?php echo $plugin_message; ?></li>
<?php
      }
    }
?>
</ul>
<form method="post">
  <input type="hidden" name="step" value="5" />
  <input type="submit" name="submit" value="Next Step - Themes" class="button button-large" />
</form>
<?php

    break;

  case 5 :

    $suggest = '';
    if( is_array( $suggestions['themes'] ) ){
      $suggest = implode( "\n" , $suggestions['themes'] );
    }elseif( is_string( $suggestions['themes'] ) ){
      if( !( $suggest = @file_get_contents( $suggestions['themes'] ) ) )
        $suggest = '';
    }

?>
<!-- STEP 5 //-->
<h1>步骤5/7:安装主题</h1>
<p>列出所有WordPress主题的下载url，每行一个</p>
<form method="post">
  <textarea name="themes"><?php echo $suggest; ?></textarea>
  <input type="hidden" name="step" value="6" />
  <input type="submit" name="submit" value="Install Themes" class="button button-large" />
</form>
<?php

    break;

  case 6 :

?>
<!-- STEP 6 //-->
<h1>步骤6/7:安装主题</h1>
<ul>
<?php

    if( isset( $_POST['themes'] ) ){
      $themes = array_filter( explode( "\n" , $_POST['themes'] ) );
      foreach( $themes as $url ){
        $theme_result = false;
        $theme_message = 'UNKNOWN';
        $url = trim( $url );
        if( !$url ) continue;
        if( strpos( $url , 'http' )!==0 )
          $url = 'http://'.$url;
        preg_match( '/^(https?\:\/\/?wordpress.org\/extend\/themes\/download\/)([^\.]+)((?:\.\d+)+)\.zip$/' , $url , $bits );
        $get = @file_get_contents( $url );
        if( !$get ){
          $theme_message = 'FAILED TO DOWNLOAD';
        }else{
          file_put_contents( 'temp_theme.zip' , $get );
          if( !extractSubFolder( 'temp_theme.zip' , dirname( __FILE__ ).'/wp-content/themes' ) ){
            $theme_message = 'FAILED TO EXTRACT';
          }else{
            $theme_result = true;
            $theme_message = 'OK';
          }
?>
  <li class="<?php echo ( $theme_result ? 'pass' : 'fail' ); ?>">安装 <strong><?php echo $bits[2]; ?>.zip</strong> - <?php echo $theme_message; ?></li>
<?php
          @unlink( 'temp_theme.zip' );
        }
        echo '</li>';
      }
    }

?>
</ul>
<form method="post">
  <input type="hidden" name="step" value="7" />
  <input type="submit" name="submit" value="Next Step - Clean Up" class="button button-large" />
</form>
<?php

    break;

  case 7 :

?>
<!-- STEP 7 //-->
<h1>步骤7/7:清理</h1>
<ul>
<?php

    $tests = array(
      array(
        'result' => ( !file_exists( 'wordpress.zip' ) || @unlink( 'wordpress.zip' ) ) ,
        'pass' => 'Remove WordPress Installer - OK' ,
        'fail' => 'Remove WordPress Installer - FAILED'
      ) ,
      array(
        'result' => ( !file_exists( 'temp_plugin.zip' ) || @unlink( 'temp_plugin.zip' ) ) ,
        'pass' => 'Remove Temporary Plugin File - OK' ,
        'fail' => 'Remove Temporary Plugin File - FAILED'
      ) ,
      array(
        'result' => ( !file_exists( 'temp_theme.zip' ) || @unlink( 'temp_theme.zip' ) ) ,
        'pass' => 'Remove Temporary Theme File - OK' ,
        'fail' => 'Remove Temporary Theme File - FAILED'
      ) ,
      array(
        'result' => ( !file_exists( __FILE__ ) || @unlink( __FILE__ ) ) ,
        'pass' => 'Remove WordPress Remote Installer - OK' ,
        'fail' => 'Remove WordPress Remote Installer - FAILED'
      ) ,
    );

    foreach( $tests as $t ){
?>
  <li class="<?php echo ( $t['result'] ? 'pass' : 'fail' ); ?>"><?php echo $t[( $t['result'] ? 'pass' : 'fail' )]; ?></li>
<?php
    }
?>
</ul>
<form method="post" action="./wp-admin/setup-config.php">
  <input type="submit" name="submit" value="Launch WordPress Installer" class="button button-large" />
</form>
<?php

    break;
}

?>

<div id="footer">
  
  由 <a href="http://www.lhempire.com">狼豪网络</a>创建<br/>
  <div class="legal">
    
  </div>
</div>

<script src="//code.jquery.com/jquery.min.js"></script>
<script src="//<?php echo GITHUB_USERNAME; ?>.github.io/<?php echo GITHUB_PROJECT; ?>/javascripts/installer.js"></script>


</body>
</html>
