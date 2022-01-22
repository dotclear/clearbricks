<?php
/**
 * @package Clearbricks
 *
 * Tiny library including:
 * - Database abstraction layer (MySQL/MariadDB, postgreSQL and SQLite)
 * - File manager
 * - Feed reader
 * - HTML filter/validator
 * - Images manipulation tools
 * - Mail utilities
 * - HTML pager
 * - REST Server
 * - Database driven session handler
 * - Simple Template Systeme
 * - URL Handler
 * - Wiki to XHTML Converter
 * - HTTP/NNTP clients
 * - XML-RPC Client and Server
 * - Zip tools
 * - Diff tools
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 * @version 1.0
 */
require __DIR__ . '/common/_main.php';

# Database Abstraction Layer
$__autoload['dbLayer']  = __DIR__ . '/dblayer/dblayer.php';
$__autoload['dbStruct'] = __DIR__ . '/dbschema/class.dbstruct.php';
$__autoload['dbSchema'] = __DIR__ . '/dbschema/class.dbschema.php';

# Files Manager
$__autoload['filemanager'] = __DIR__ . '/filemanager/class.filemanager.php';
$__autoload['fileItem']    = __DIR__ . '/filemanager/class.filemanager.php';

# Feed Reader
$__autoload['feedParser'] = __DIR__ . '/net.http.feed/class.feed.parser.php';
$__autoload['feedReader'] = __DIR__ . '/net.http.feed/class.feed.reader.php';

# HTML Filter
$__autoload['htmlFilter'] = __DIR__ . '/html.filter/class.html.filter.php';

# HTML Validator
$__autoload['htmlValidator'] = __DIR__ . '/html.validator/class.html.validator.php';

# Image Manipulation Tools
$__autoload['imageMeta']  = __DIR__ . '/image/class.image.meta.php';
$__autoload['imageTools'] = __DIR__ . '/image/class.image.tools.php';

# Send Mail Utilities
$__autoload['mail'] = __DIR__ . '/mail/class.mail.php';

# Mail Convert and Rewrap
$__autoload['mailConvert'] = __DIR__ . '/mail.convert/class.mail.convert.php';

# Send Mail Through Sockets
$__autoload['socketMail'] = __DIR__ . '/mail/class.socket.mail.php';

# Mime Message Parser
$__autoload['mimeMessage'] = __DIR__ . '/mail.mime/class.mime.message.php';

# HTML Pager
$__autoload['pager'] = __DIR__ . '/pager/class.pager.php';

# REST Server
$__autoload['restServer'] = __DIR__ . '/rest/class.rest.php';
$__autoload['xmlTag']     = __DIR__ . '/rest/class.rest.php';

# Database PHP Session
$__autoload['sessionDB'] = __DIR__ . '/session.db/class.session.db.php';

# Simple Template Systeme
$__autoload['template']               = __DIR__ . '/template/class.template.php';
$__autoload['tplNode']                = __DIR__ . '/template/class.tplnode.php';
$__autoload['tplNodeBlock']           = __DIR__ . '/template/class.tplnodeblock.php';
$__autoload['tplNodeText']            = __DIR__ . '/template/class.tplnodetext.php';
$__autoload['tplNodeValue']           = __DIR__ . '/template/class.tplnodevalue.php';
$__autoload['tplNodeBlockDefinition'] = __DIR__ . '/template/class.tplnodeblockdef.php';
$__autoload['tplNodeValueParent']     = __DIR__ . '/template/class.tplnodevalueparent.php';

# URL Handler
$__autoload['urlHandler'] = __DIR__ . '/url.handler/class.url.handler.php';

# Wiki to XHTML Converter
$__autoload['wiki2xhtml'] = __DIR__ . '/text.wiki2xhtml/class.wiki2xhtml.php';

# SQL Batch on XML Files
$__autoload['xmlsql'] = __DIR__ . '/xmlsql/class.xmlsql.php';

# Common Socket Class
$__autoload['netSocket'] = __DIR__ . '/net/class.net.socket.php';

# HTTP Client
$__autoload['netHttp']    = __DIR__ . '/net.http/class.net.http.php';
$__autoload['HttpClient'] = __DIR__ . '/net.http/class.net.http.php';

# NNTP Client
$__autoload['netNntp']     = __DIR__ . '/net.nntp/class.net.nntp.php';
$__autoload['nntpMessage'] = __DIR__ . '/net.nntp/class.nntp.message.php';

# XML-RPC Client and Server
$__autoload['IXR_Value']               = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Message']             = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Server']              = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Request']             = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Client']              = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Error']               = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Date']                = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_Base64']              = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_IntrospectionServer'] = __DIR__ . '/ext/incutio.ixr_library.php';
$__autoload['IXR_ClientMulticall']     = __DIR__ . '/ext/incutio.ixr_library.php';

$__autoload['xmlrpcValue']               = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcMessage']             = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcRequest']             = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcDate']                = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcBase64']              = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcClient']              = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcClientMulticall']     = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcServer']              = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcIntrospectionServer'] = __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php';

# Zip tools
$__autoload['fileUnzip'] = __DIR__ . '/zip/class.unzip.php';
$__autoload['fileZip']   = __DIR__ . '/zip/class.zip.php';

# Diff tools
$__autoload['diff']     = __DIR__ . '/diff/lib.diff.php';
$__autoload['tidyDiff'] = __DIR__ . '/diff/lib.tidy.diff.php';

# HTML Form helpers
$__autoload['formComponent'] = __DIR__ . '/html.form/class.form.component.php';
$__autoload['formForm']      = __DIR__ . '/html.form/class.form.form.php';
$__autoload['formTextarea']  = __DIR__ . '/html.form/class.form.textarea.php';
$__autoload['formInput']     = __DIR__ . '/html.form/class.form.input.php';
$__autoload['formCheckbox']  = __DIR__ . '/html.form/class.form.checkbox.php';
$__autoload['formColor']     = __DIR__ . '/html.form/class.form.color.php';
$__autoload['formDate']      = __DIR__ . '/html.form/class.form.date.php';
$__autoload['formDatetime']  = __DIR__ . '/html.form/class.form.datetime.php';
$__autoload['formEmail']     = __DIR__ . '/html.form/class.form.email.php';
$__autoload['formFile']      = __DIR__ . '/html.form/class.form.file.php';
$__autoload['formHidden']    = __DIR__ . '/html.form/class.form.hidden.php';
$__autoload['formNumber']    = __DIR__ . '/html.form/class.form.number.php';
$__autoload['formPassword']  = __DIR__ . '/html.form/class.form.password.php';
$__autoload['formRadio']     = __DIR__ . '/html.form/class.form.radio.php';
$__autoload['formTime']      = __DIR__ . '/html.form/class.form.time.php';
$__autoload['formUrl']       = __DIR__ . '/html.form/class.form.url.php';
$__autoload['formLabel']     = __DIR__ . '/html.form/class.form.label.php';
$__autoload['formFieldset']  = __DIR__ . '/html.form/class.form.fieldset.php';
$__autoload['formLegend']    = __DIR__ . '/html.form/class.form.legend.php';
$__autoload['formSelect']    = __DIR__ . '/html.form/class.form.select.php';
$__autoload['formOptgroup']  = __DIR__ . '/html.form/class.form.optgroup.php';
$__autoload['formOption']    = __DIR__ . '/html.form/class.form.option.php';
