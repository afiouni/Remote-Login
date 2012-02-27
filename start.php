<?php

	/**
	 * Remote Login plugin.
	 * 
	 * @package RemoteLogin
	 * @license http://www.opensource.org/licenses/gpl-license.php
	 * @author Khaled Afiouni
	 * @copyright skinju.com 2010
	 * @link http://skinju.com/elgg/remote-login
	 */


register_plugin_hook('login_required','login_required', 'remotelogin_login_required_allow_remote_login');
function remotelogin_login_required_allow_remote_login ($hook, $type, $returnvalue, $params)
{
  global $CONFIG;

  $returnvalue[] = $CONFIG->url . 'pg/remotelogin';
  $returnvalue[] = $CONFIG->url . 'pg/remotelogin/style.css';
  $returnvalue[] = $CONFIG->url . 'pg/remotelogin/load.js';
  $returnvalue[] = $CONFIG->url . 'pg/remotelogin/run.js';
  $returnvalue[] = $CONFIG->url . 'pg/remotelogin/remoteajax.js';
  return $returnvalue;
}


register_elgg_event_handler('login','user','remotelogin_login_event_hook');
function remotelogin_login_event_hook ($event, $object_type, $object)
{
  $_SESSION['remote_login_referer'] = $_SERVER['HTTP_REFERER'];
  return true;
}

register_action("logout",false,$CONFIG->pluginspath . "remotelogin/actions/logout.php", true); 

register_page_handler('remotelogin', 'remotelogin_handler');
function remotelogin_handler ($page)
{
  global $CONFIG;
  switch (true)
  {
    case (isset($page[0]) && $page[0] === 'load.js'):
      header ('Content-Type: text/javascript; charset=UTF-8');

      echo 'document.write([';
      echo '"\<script type=\'text/javascript\' src=\'",';
      echo '("https:" == document.location.protocol) ? "https://" : "http://",';
      echo '"ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js\'>\<\/script>"'; 
      echo '].join(""));';

      echo 'document.write([';
      echo '"\<script type=\'text/javascript\' src=\'",';
      echo '("https:" == document.location.protocol) ? "https://" : "http://",';
      echo '"' . preg_replace("/^https?:\/\/(.+)$/i","\\1", $CONFIG->url) . 'pg/remotelogin/remoteajax.js\'>\<\/script>"'; 
      echo '].join(""));';

      echo 'document.write([';
      echo '"\<link rel=\'stylesheet\' href=\'",';
      echo '("https:" == document.location.protocol) ? "https://" : "http://",';
      echo '"' . preg_replace("/^https?:\/\/(.+)$/i","\\1", $CONFIG->url) . 'pg/remotelogin/style.css\' type=\'text\/css\'/>"';
      echo '].join(""));';

      echo 'document.write([';
      echo '"\<div id=\'remotelogin\'>\<\/div>"';
      echo '].join(""));';

      echo 'document.write([';
      echo '"\<script type=\'text/javascript\' src=\'",';
      echo '("https:" == document.location.protocol) ? "https://" : "http://",';
      echo '"' . preg_replace("/^https?:\/\/(.+)$/i","\\1", $CONFIG->url) . 'pg/remotelogin/run.js\'>\<\/script>"'; 
      echo '].join(""));';
      break;

    case (isset($page[0]) && $page[0] === 'run.js'):
      header ('Content-Type: text/javascript; charset=UTF-8');

      echo 'loading_img = ("https:" == document.location.protocol) ? "https://" : "http://" + "' . preg_replace("/^https?:\/\/(.+)$/i","\\1", $CONFIG->url) . '_graphics\/ajax_loader.gif";';
      echo 'loading_img = \'<img class="loading"  alt="loading..." src="\' + loading_img + \'" />\';';

      echo 'login_box_url = ("https:" == document.location.protocol) ? "https://" : "http://" + "' . preg_replace("/^https?:\/\/(.+)$/i","\\1", $CONFIG->url) . 'pg/remotelogin #login-box";';

      echo '$(document).ready(function() {';
      echo '$.ajaxSetup ({cache: false});';
      echo '$(\'#remotelogin\').html(loading_img)';
      echo '.load(login_box_url);});';

      break;

    case (isset($page[0]) && $page[0] === 'remoteajax.js'):
      header ('Content-Type: text/javascript; charset=UTF-8');
      echo <<< END
        /**
         * jQuery.ajax mid - CROSS DOMAIN AJAX 
         * ---
         * @author James Padolsey (http://james.padolsey.com)
         * @version 0.11
         * @updated 12-JAN-10
         * ---
         * Note: Read the README!
         * ---
         * @info http://james.padolsey.com/javascript/cross-domain-requests-with-jquery/
         */

        jQuery.ajax = (function(_ajax){
          var protocol = location.protocol,
          hostname = location.hostname,
          exRegex = RegExp(protocol + '//' + hostname),
          YQL = 'http' + (/^https/.test(protocol)?'s':'') + '://query.yahooapis.com/v1/public/yql?callback=?',
          query = 'select * from html where url="{URL}" and xpath="*"';
    
          function isExternal(url) {return !exRegex.test(url) && /:\/\//.test(url);}
    
          return function(o)
          {
            var url = o.url;
            if ( /get/i.test(o.type) && !/json/i.test(o.dataType) && isExternal(url) ) {
              // Manipulate options so that JSONP-x request is made to YQL
              o.url = YQL;
              o.dataType = 'json';
            
              o.data = {
                q: query.replace(
                    '{URL}',
                    url + (o.data ?
                        (/\?/.test(url) ? '&' : '?') + jQuery.param(o.data)
                    : '')
                ),
                format: 'xml'
            };
            
            // Since it's a JSONP request
            // complete === success
            if (!o.success && o.complete) {
                o.success = o.complete;
                delete o.complete;
            }
            
            o.success = (function(_success){
                return function(data) {
                    
                    if (_success) {
                        // Fake XHR callback.
                        _success.call(this, {
                            responseText: data.results[0]
                                // YQL screws with <script>s
                                // Get rid of them
                                .replace(/<script[^>]+?\/>|<script(.|\s)*?\/script>/gi, '')
                        }, 'success');
                    }
                    
                };
            })(o.success);
          }
          return _ajax.apply(this, arguments);
          };
        })(jQuery.ajax);
END;
      break;

    case (isset($page[0]) && $page[0] === 'style.css'):
      header ('Content-Type: text/css; charset=UTF-8');
      echo <<< END
#remotelogin, #remotelogin * {
	margin: 0;
	padding: 0;
	border: 0;
	outline: 0;
	font-weight: inherit;
	font-style: inherit;
	font-size: 100%;
	font-family: inherit;
	vertical-align: baseline;}

#remotelogin {
	font: 80%/1.4  "Lucida Grande", Verdana, sans-serif;
	color: #333333;}

#remotelogin a {
	color: #4690d6;
	text-decoration: none;
	-moz-outline-style: none;
	outline: none;}

#remotelogin a:hover {
	color: #0054a7;
	text-decoration: underline;}

#remotelogin #login-box {
	margin:0 0 10px 0;
	padding:0 0 10px 0;
	background: #dedede;
	-webkit-border-radius: 8px;
	-moz-border-radius: 8px;
	width:240px;
	text-align:left;}

#remotelogin #login-box form {
	margin:0 10px 0 10px;
	padding:0 10px 4px 10px;
	background: white;
	-webkit-border-radius: 8px;
	-moz-border-radius: 8px;
	width:200px;}

#remotelogin #login-box h2 {
	color:#0054A7;
	font-size:1.35em;
	line-height:1.2em;
	margin:0 0 0 8px;
	padding:5px 5px 0 5px;}

#remotelogin #login-box .login-textarea {
	width:178px;}

#remotelogin #login-box label,
#remotelogin #register-box label {
	font-size: 1.2em;
	color:gray;}

#remotelogin #login-box p.loginbox {
	margin:0;}

#remotelogin #login-box input[type="text"],
#remotelogin #login-box input[type="password"],
#remotelogin #register-box input[type="text"],
#remotelogin #register-box input[type="password"] {
	margin:0 0 10px 0;}

#remotelogin #register-box input[type="text"],
#remotelogin #register-box input[type="password"] {
	width:380px;}

#remotelogin #login-box h2,
#remotelogin #login-box-openid h2,
#remotelogin #register-box h2,
#remotelogin #add-box h2,
#remotelogin #forgotten_box h2 {
	color:#0054A7;
	font-size:1.35em;
	line-height:1.2em;
	margin:0pt 0pt 5px;}

#remotelogin label {
	font-weight: bold;
	color:#333333;
	font-size: 120%;}

#remotelogin input {
	font: 120% Arial, Helvetica, sans-serif;
	padding: 5px;
	border: 1px solid #cccccc;
	color:#666666;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;}

#remotelogin input[type="checkbox"] {
	padding: 1px;
	border-style: none;}

#remotelogin textarea {
	font: 120% Arial, Helvetica, sans-serif;
	border: solid 1px #cccccc;
	padding: 5px;
	color:#666666;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;}

#remotelogin textarea:focus,
#remotelogin input[type="text"]:focus {
	border: solid 1px #4690d6;
	background-color: #e4ecf5;
	color:#333333;}

#remotelogin .submit_button {
	font: 12px/100% Arial, Helvetica, sans-serif;
	font-weight: bold;
	color: #ffffff;
	background:#4690d6;
	border: 1px solid #4690d6;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	width: auto;
	height: 25px;
	padding: 2px 6px 2px 6px;
	margin:10px 0 10px 0;
	cursor: pointer;}

#remotelogin .submit_button:hover,
#remotelogin input[type="submit"]:hover {
	background: #0054a7;
	border-color: #0054a7;}

#remotelogin input[type="submit"] {
	font: 12px/100% Arial, Helvetica, sans-serif;
	font-weight: bold;
	color: #ffffff;
	background:#4690d6;
	border: 1px solid #4690d6;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	width: auto;
	height: 25px;
	padding: 2px 6px 2px 6px;
	margin:10px 0 10px 0;
	cursor: pointer;}

#remotelogin .input-password,
#remotelogin .input-text,
#remotelogin .input-tags,
#remotelogin .input-url,
#remotelogin .input-textarea {
	width:98%;}

#remotelogin .input-textarea {
	height: 200px;}

END;
      break;

    default:
      header("Content-type: text/html; charset=UTF-8");
      echo '<html>';
      echo '<head>';
      echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
      echo '<title>' . $CONFIG->sitename . ' : Login</title>';
      echo '<link rel="stylesheet" href="' . $CONFIG->url . '_css/css.css?lastcache=' . $CONFIG->lastcache . '&amp;viewtype=default" type="text/css" />';
      echo '</head>';
      echo '<body>';
      echo elgg_view("account/forms/login");
      echo '</body>';
      echo '</html>';
  }
}
?>