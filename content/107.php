  
<style type="text/css">
<!--
pre {
	white-space: normal;
}
-->
</style>
<p><strong>About the Translator</strong></p>
<p>A &quot;translated&quot; field or page is one where you cannot use standard HTML except
  for the following tags:</p>
<ul>
  <li>&lt;b&gt;</li>
  <li>&lt;i&gt;</li>
  <li>&lt;u&gt;</li>
  <li>&lt;br&gt;</li>
  <li>&lt;p&gt;</li>
  <li>&lt;h2&gt;</li>
</ul>
<p>To insert a link to a page on camdram.net, use &#91;CAMDRAMNET:id] where id is the ID or menu entry of the page you wish to link to, e.g. &#91;CAMDRAMNET:107] or &#91;CAMDRAMNET:translator] links to this page:</p><p>[CAMDRAMNET:107]</p>
<p>To insert a link to a website, use &#91;L:URL] e.g. </p>
<pre>see our website at &#91;L:www.camdram.net]</pre>
(you can omit http://) which generates:</p><p>see our website at [L:www.camdram.net]</p><p>or in
HTML:
<pre>see our website at [&lt;a href=&quot;http://www.camdram.net&quot; target=&quot;_blank&quot;&gt;www.camdram.net&lt;/a&gt;]</pre>
<p>Insert email addresses using &#91;E:address] e.g.</p>
<pre>contact Andrew Pontzen &#91;E:app26]</pre>
<p></p>
(if there is no @ it will be assumed you mean @cam.ac.uk), which generates the
following:</p><p>contact Andrew Pontzen [E:app26]</p><p>in HTML:
<pre>contact Andrew Pontzen [&lt;a href=&quot;mailto:app26@cam.ac.uk&quot;&gt;app26&lt;/a&gt;]</pre>
<p>For any of these functions, you may override the default link text by using &#91;LINKTYPE:linkresource;linktext], e.g. &#91;CAMDRAMNET:107;Royal National Theatre] generates the following:</p><p> [CAMDRAMNET:107;Royal National Theatre]</p><p> or in HTML:<pre>&lt;a href=&quot;micro.php?id=107&quot;&gt;Royal National Theatre&lt;/a&gt;</pre>
