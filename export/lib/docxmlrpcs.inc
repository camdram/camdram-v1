<?php
/**
 * Self-documenting extension to the PHP-XMLRPC server
 *
 * @author Gaetano Giunta
 * @version $Id: docxmlrpcs.inc 972 2006-07-25 19:59:16Z john $
 * @copyright (c) 2005 G. Giunta
 *
 * @todo use some AJAX magic to implement xmlrpc calls to test/debug methods
 *       without feeding to user the raw xml
 * @todo add some i18n support
 * @todo add a sane way to have a set of hhtp headers to be sent along with every
 *       type of generated documentation (eg. content-type)
 **/

	// requires: xmlrpc.inc, xmlrpcs.inc (version 2.0RC3 or later)

	class documenting_xmlrpc_server extends xmlrpc_server
	{
		/// default format for generated documentation: either wsdl or html
		var $default_doctype = 'html';
		var $default_doclang = 'en';
		var $supported_langs = array('en');
		var $supported_doctypes = array('html', 'wsdl');

		/**
		* Override xmlrpc_server service method:
		*   in case of GET requests show docs about implemented methods;
		*   in case of POST received by a form, we use the methodCall input value
		*   as if it had been sent with a tex/xml mimetype
		* @param string $data    request data to be parsed, null by default
		* @param string $doctype type of documentation to generate: html, wsdl, etc... If empty, use class default
		*/
		function service($data=null, $doctype='')
		{
			if($_SERVER['REQUEST_METHOD'] != 'POST')
			{
				if ($doctype == '' || !in_array($doctype, $this->supported_doctypes))
				{
					$doctype = $this->default_doctype;
				}
				// language decoding
				if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), $this->supported_langs))
				{
				    $lang = strtolower($_GET['lang']);
				}
				else
				{
				    $lang = $this->default_doclang;
				}

				print generateDocs($this, $doctype, $lang);
			}
			else
			{
				// we break the xmlrpc spec here, and answer to POST requests
				// that have been sent via a standard html form, such as the
				// one that is part of self-generated docs
				if(isset($_SERVER['CONTENT_TYPE'])
					&& $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded'
					&& isset($_POST['methodCall']))
				{
					parent::service($_POST['methodCall']);
				}
				else
				{
					parent::service($data);
				}
			}
		}

	}


	/**
	* Generate the documentation about methods exposed by a given server.
	* @param xmlrpcserver $server
	* @param string $doctype type of documentation to generate: html (default), wsdl, etc...
	* @param string $lang language for docs
	* @return string
	*
	* @todo add support for i18n of generated user-readable docs (eg html)
	*/
	function generateDocs($server, $doctype='html', $lang='en')
	{
		$payload = '';
		switch ($doctype)
		{
			case 'wsdl':
				break;
			case 'html':
				//$i18n = $GLOBALS['xmlrpcdoci18n'][$lang];
				$template = $GLOBALS['xmlrpcdocparts']['html'];
				// in case we have to send custom http headers, do it
				// removed from here, since we only return the payload now...
				//foreach ($template['httpheaders'] as $header)
				//	header($header);

				// method name decoding: is uer seeking info about a single method?
				if (isset($_GET['methodName']))
				{
					$payload .= xmlrpc_smarty($template['docheader'], array('lang' => $lang, 'title' => 'Method '.$_GET['methodName']));
					if ($server->allow_system_funcs)
					{
						$methods = array_merge($server->dmap, $GLOBALS['_xmlrpcs_dmap']);
					}
					else
					{
						$methods = $server->dmap;
					}
					if (!array_key_exists($_GET['methodName'], $methods))
					{
						$payload .= xmlrpc_smarty($template['methodheader'], array('method' => $_GET['methodName'], 'desc' => ''));
						$payload .= xmlrpc_smarty($template['methodnotfound'], array('method' => $_GET['methodName']));
					}
					else
					{
						$payload .= xmlrpc_smarty($template['methodheader'], array('method' => $_GET['methodName'], 'desc' => @$methods[$_GET['methodName']]['docstring']));
						//$payload .= xmlrpc_smarty($template['methodfound']);
						$i = 1;
						$ol=0;
						foreach ($methods[$_GET['methodName']]['signature'] as $val)
						{
							
							$payload .= xmlrpc_smarty($template['sigheader'], array('signum' => $i));
							$out = array_shift($val);
							$doc=$methods[$_GET['methodName']]['sigdoc'][$ol];
							$outdoc=array_shift($doc);
							$par=0;
							foreach ($val as $key)
							{
								$payload .= xmlrpc_smarty($template['sigparam'], array('paramtype' => $key, 'paramdesc' => $doc[$par]));
								$par++;
							}
							$payload .= xmlrpc_smarty($template['sigfooter'], array('outtype' => $out, 'method' => $_GET['methodName'],'outdesc' => $outdoc));
							$i++;
							$ol++;
						}
						$payload .= xmlrpc_smarty($template['methodfooter'], array('method' => $_GET['methodName']));
					}
				}
				else
				{
					// complete api info
					$payload .= xmlrpc_smarty($template['docheader'], array('lang' => $lang, 'title' => 'API Index'));
					$payload .= xmlrpc_smarty($template['apiheader']);
					foreach($server->dmap as $key => $val)
					{
						$payload .= xmlrpc_smarty($template['apimethod'], array('method' => $key, 'desc' => @$val['docstring']));
					}
					if($server->allow_system_funcs)
					{
						foreach($GLOBALS['_xmlrpcs_dmap'] as $key => $val)
						{
							$payload .= xmlrpc_smarty($template['apimethod'], array('method' => $key, 'desc' => @$val['docstring']));
						}
					}
					$payload .= xmlrpc_smarty($template['apifooter']);
				}

				$payload .= xmlrpc_smarty($template['docfooter']);

		}
		return $payload;
	}

	/** Dumb smarty-like template system
	* @param string $template the template text, using {$var} syntax for substitution
	* @param array $params array of variables to be substituted in template, based on array key
	*
	* @todo introduce support for multilanguage directly here
	* @todo introduce support for nested arrays, so we can coalesce templates
	*/
	function xmlrpc_smarty($template, $params=array())
	{
		foreach ($params as $key => $val)
		{
			$template = str_replace("{\$$key}", $val, $template);
		}
		return $template;
	}

	/// Templates used for building docs
	$GLOBALS['xmlrpcdocparts'] = array(
		'html' => array(

//'httpheaders' => array(),

'docheader' => '<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$lang}" lang="{$lang}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<meta http-equiv="Content-Script-Type" content="text-javascript" />
<meta name="generator" content="'.$GLOBALS['xmlrpcName'].'" />
<link rel="stylesheet" type="text/css" href="docxmlrpcs.css" />
<title>{$title}</title>
</head>
<body>',

'docfooter' => '
<div class="footer">Generated using PHP-XMLRPC '.$GLOBALS['xmlrpcVersion'].'</div>
</body></html>',

'apiheader' => '
<h1>API index</h1>
<p>This server defines the following API specification:</p>
<table class="apilist">
<tr><th>Method</th><th>Description</th></tr>',

'apimethod' => '
<tr><td><a href="?methodName={$method}">{$method}</a></td><td>{$desc}</td></tr>',

'apifooter' => '
</table>',

'methodheader' => '
<h1>Method <em>{$method}</em></h1>
<div>{$desc}</div>',

'methodnotfound' => '
<h3>The method {$method} is not part of the API of this server</h3>
',

'sigheader' => '
<h2>Signature {$signum}</h2>
<blockquote>
<h3>Input parameters</h3>
<table class="inputparameters">
<tr><th>Type</th><th>Description</th></tr>',

'sigparam' => '
<tr><td>{$paramtype}</td><td>{$paramdesc}</td></tr>',

'sigfooter' => '
</table>
<h3>Output parameter</h3>
<table class="inputparameters">
<tr><th>Type</th><th>Description</th></tr>
<tr><td>{$outtype}</td><td>{$outdesc}</td></tr>
</table>
</blockquote>',

'methodfooter' => '
<h2>Test method call</h2>
<p>Complete by hand the form below inserting the needed parameters to call this method.<br/>
For a string param use e.g. <pre>&lt;param&gt;&lt;value&gt;&lt;string&gt;Hello&lt;/string&gt;&lt;/value&gt;&lt;/param&gt;</pre></p>
<form action="" method="post"><p>
<textarea name="methodCall" rows="5" cols="80">
&lt;methodCall&gt;&lt;methodName&gt;{$method}&lt;/methodName&gt;
&lt;params&gt;
&lt;/params&gt;
&lt;/methodCall&gt;
</textarea><br/>
<input type="submit" value="Test"/>
</p></form>'
	    ),

		'wsdl' => array(
		)
	);

/*
	/// internationalization of docs templates
	$GLOBALS['xmlrpcdoci18n'] = array(
		'en' => array (
			'apiindex' => 'API Index'
		)
	);
*/
?>
